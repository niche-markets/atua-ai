'use strict';

import { Remarkable } from 'remarkable';
import hljs from 'highlight.js'
import { linkify } from 'remarkable/linkify';

hljs.configure({ ignoreUnescapedHTML: true });
hljs.safeMode();

var md = new Remarkable({
    html: false,        // Enable HTML tags in source
    xhtmlOut: false,        // Use '/' to close single tags (<br />)
    breaks: true,        // Convert '\n' in paragraphs into <br>
    langPrefix: 'language-',  // CSS language prefix for fenced blocks

    // Enable some language-neutral replacement + quotes beautification
    typographer: false,

    // Double + single quotes replacement pairs, when typographer enabled,
    // and smartquotes on. Set doubles to '«»' for Russian, '„“' for German.
    quotes: '“”‘’',

    // Highlighter function. Should return escaped HTML,
    // or '' if the source string is not changed
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (err) { }
        }

        try {
            return hljs.highlightAuto(text).value;
        } catch (err) { }

        return ''; // use external default escaping
    }
});

md.use(linkify);

export function markdownToHtml(text) {
    let html = md.render(text);

    let el = document.createElement('div');
    el.innerHTML = html;

    el.querySelectorAll('pre code').forEach((el) => {
        let text = el.innerText.trim();

        let actions = document.createElement('div');
        actions.classList.add('actions');

        let lang = document.createElement('span');
        lang.classList.add('lang');
        let match = el.className.match(/language-(\w+)/);
        lang.innerText = match ? match[1] : 'text';

        actions.appendChild(lang);

        let copy = document.createElement('x-copy');
        copy.classList.add('copy');

        let icon = document.createElement('i');
        icon.classList.add('ti', 'ti-copy');

        copy.appendChild(icon);
        copy.setAttribute('data-copy', text);

        actions.appendChild(copy);

        el.closest('pre').prepend(actions);
    });

    return el.innerHTML;
}