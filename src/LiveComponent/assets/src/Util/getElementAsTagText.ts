/**
 * Returns just the outer element's HTML as a string - useful for error messages.
 *
 * For example:
 *      <div class="outer">And text inside <p>more text</p></div>
 *
 * Would return:
 *      <div class="outer">
 */
export default function getElementAsTagText(element: HTMLElement): string {
    return element.innerHTML
        ? element.outerHTML.slice(0, element.outerHTML.indexOf(element.innerHTML))
        : element.outerHTML;
}
