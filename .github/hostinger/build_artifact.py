"""Seal and restore the complete verified Vite build for one commit/workflow run."""
import argparse
import hashlib
import json
import os
from pathlib import Path, PurePosixPath
import re
import shutil
import tarfile


ENTRIES = ("resources/css/app.css", "resources/js/app.ts", "resources/js/public-blog.ts")


def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def safe_name(name):
    path = PurePosixPath(name)
    if not re.fullmatch(r"[A-Za-z0-9_./-]+", name) or path.is_absolute() or ".." in path.parts or str(path) != name:
        raise ValueError(f"Unsafe build path: {name!r}")
    return name


def inventory(root):
    if not root.is_dir() or root.is_symlink():
        raise ValueError("Vite build is missing or symlinked")
    files = {}
    for path in sorted(root.rglob("*")):
        if path.is_symlink():
            raise ValueError(f"Symlink in build: {path}")
        if path.is_file():
            files[safe_name(path.relative_to(root).as_posix())] = digest(path)
    return files


def validate_build(root):
    files = inventory(root)
    manifest = json.loads((root / "manifest.json").read_text(encoding="utf-8"))
    for entry in ENTRIES:
        if entry not in manifest or not manifest[entry].get("isEntry"):
            raise ValueError(f"Missing Vite entry: {entry}")
    for chunk in manifest.values():
        for name in [chunk["file"], *chunk.get("css", []), *chunk.get("assets", [])]:
            if safe_name(name) not in files:
                raise ValueError(f"Missing Vite dependency: {name}")
        for key in [*chunk.get("imports", []), *chunk.get("dynamicImports", [])]:
            if key not in manifest:
                raise ValueError(f"Missing Vite chunk: {key}")
    fonts = json.loads((root / "fonts-manifest.json").read_text(encoding="utf-8"))
    for name in file_references(fonts):
        if safe_name(name) not in files:
            raise ValueError(f"Missing font dependency: {name}")
    return files


def file_references(value):
    if isinstance(value, dict):
        for key, child in value.items():
            if key == "file":
                yield child
            else:
                yield from file_references(child)
    elif isinstance(value, list):
        for child in value:
            yield from file_references(child)


def identity(environment):
    sha, run = environment.get("GITHUB_SHA", ""), environment.get("GITHUB_RUN_ID", "")
    if not re.fullmatch(r"[a-f0-9]{40}", sha) or not run.isdigit():
        raise ValueError("Artifact requires GITHUB_SHA and GITHUB_RUN_ID")
    return dict(commit=sha, run_id=run)


def provenance_path(environment):
    return Path(environment["RUNNER_TEMP"]) / "hostinger-build-provenance.json"


def verify_build(source, environment):
    metadata = json.loads(provenance_path(environment).read_text(encoding="utf-8"))
    if any(metadata.get(key) != value for key, value in identity(environment).items()):
        raise ValueError("Build artifact belongs to another commit/workflow run")
    if metadata["files"] != validate_build(source / "public/build"):
        raise ValueError("Build bytes differ from the verified artifact")


def seal(source, folder, environment):
    root = source / "public/build"
    files = validate_build(root)
    folder.mkdir(parents=True, exist_ok=True)
    archive = folder / "build.tar"
    with tarfile.open(archive, "w") as stream:
        for name in files:
            stream.add(root / name, arcname="public/build/" + name, recursive=False)
    metadata = dict(version=1, **identity(environment), files=files, archive_sha256=digest(archive))
    (folder / "provenance.json").write_text(json.dumps(metadata, indent=2) + "\n", encoding="utf-8")
    output = environment.get("GITHUB_OUTPUT")
    if output:
        with open(output, "a", encoding="utf-8") as stream:
            stream.write(f"sha256={metadata['archive_sha256']}\n")
    print(f"Sealed {len(files)} files for {metadata['commit']}; SHA256 {metadata['archive_sha256']}")


def restore(source, folder, expected, environment):
    metadata = json.loads((folder / "provenance.json").read_text(encoding="utf-8"))
    archive = folder / "build.tar"
    if not re.fullmatch(r"[a-f0-9]{64}", expected) or digest(archive) != expected or metadata.get("archive_sha256") != expected:
        raise ValueError("Artifact checksum differs from verify job output")
    if metadata.get("version") != 1 or any(metadata.get(key) != value for key, value in identity(environment).items()):
        raise ValueError("Artifact belongs to another commit/workflow run")
    root = source / "public/build"
    if root.exists() or root.is_symlink() or (source / "public").is_symlink():
        raise ValueError("Restore requires a fresh checkout without a build or symlinked public root")
    try:
        with tarfile.open(archive) as stream:
            members = stream.getmembers()
            names = [member.name for member in members]
            if len(names) != len(set(names)) or set(names) != {"public/build/" + name for name in metadata["files"]}:
                raise ValueError("Artifact file set is invalid")
            for member in members:
                safe_name(member.name)
                if not member.isfile() or not member.name.startswith("public/build/"):
                    raise ValueError("Artifact contains a link or unsupported member")
            for member in members:
                path = source / member.name
                path.parent.mkdir(parents=True, exist_ok=True)
                with stream.extractfile(member) as incoming, path.open("wb") as outgoing:
                    shutil.copyfileobj(incoming, outgoing)
                path.chmod(0o644)
        if validate_build(root) != metadata["files"]:
            raise ValueError("Artifact build content is invalid")
        provenance_path(environment).write_text(json.dumps(metadata), encoding="utf-8")
    except Exception:
        if root.is_dir() and not root.is_symlink():
            shutil.rmtree(root)
        raise
    print(f"Restored verified artifact: {len(metadata['files'])} files, commit {metadata['commit']}, run {metadata['run_id']}")


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("phase", choices=["seal", "restore"])
    parser.add_argument("--directory", default="frontend-artifact")
    parser.add_argument("--sha256", default="")
    args = parser.parse_args()
    if args.phase == "seal":
        seal(Path.cwd(), Path(args.directory), os.environ)
    else:
        restore(Path.cwd(), Path(args.directory), args.sha256, os.environ)


if __name__ == "__main__":
    main()
