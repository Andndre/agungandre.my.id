export async function renderBlogMath(
    root: ParentNode = document,
): Promise<void> {
    const elements = root.querySelectorAll<HTMLElement>('[data-math]');

    if (!elements.length) {
        return;
    }

    const [{ default: katex }] = await Promise.all([
        import('katex'),
        import('katex/dist/katex.min.css'),
    ]);

    for (const element of elements) {
        const formula =
            element.dataset.mathFormula ?? element.textContent ?? '';
        element.dataset.mathFormula = formula;

        katex.render(formula, element, {
            displayMode: element.dataset.math === 'display',
            throwOnError: false,
        });
    }
}
