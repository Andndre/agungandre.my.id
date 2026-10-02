# Blog media storage

The editor uploads images through Laravel. Its `s3` disk uses the `AWS_*` values in `.env`; set `BLOG_MEDIA_DISK=s3` and `BLOG_OWNER_EMAIL` to the email of the account allowed to write posts. An empty owner email denies access.

For local EnvKit MinIO, use its actual API endpoint and credentials. Set `AWS_ENDPOINT` to that endpoint, `AWS_BUCKET` to the blog media bucket, `AWS_USE_PATH_STYLE_ENDPOINT=true`, and `AWS_URL` to the browser-accessible public base URL including the bucket path. Give the bucket public read access so article images can load. Keep `FILESYSTEM_DISK=local` if the rest of the application uses local storage.

For production Cloudflare R2, use an R2 token scoped to the bucket with object read/write access. Set `AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com`, `AWS_DEFAULT_REGION=auto`, `AWS_BUCKET`, `AWS_ACCESS_KEY_ID`, and `AWS_SECRET_ACCESS_KEY`. Set `AWS_URL` to the bucket's existing public custom domain and `AWS_USE_PATH_STYLE_ENDPOINT=false`. Uploads stay server-side; the browser receives only the public image URL.

After configuring each environment, upload an image in the editor and open its returned URL in a private browser window. Uploaded objects are retained when a post is deleted because an image URL may be reused by another post.

The upload endpoint accepts JPEG, PNG, WebP, and GIF files up to 10 MB. Set PHP `upload_max_filesize` to at least `10M` and `post_max_size` above `10M` in both environments.
