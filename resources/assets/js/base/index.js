`use strict`;

import { width } from './scrollbar.js';

// Import custom HTML elements
import { Modal, ModalController } from './modal.js';
import { Toast } from './toast.js';
import { ModeSwitcher } from './mode-switcher.js';
import { Uuid } from './uuid.js';
import { CopyElement } from './copy-element.js';
import { TimeElement } from './time-element.js';
import { MoneyElement } from './money-element.js';
import { CreditElement } from './credit-element.js';
import { FormElement } from './form-element.js';
import { BlurHash } from './blurhash.js';
import { WaveElement } from './wave.js';
import { ChartElement } from './chart.js';
import { MarkdownElement } from './markdown-element.js';

// Define custom elements
customElements.define('mode-switcher', ModeSwitcher);
customElements.define('toast-message', Toast);
customElements.define('modal-element', Modal);
customElements.define('x-uuid', Uuid)
customElements.define('x-copy', CopyElement);
customElements.define('x-time', TimeElement);
customElements.define('x-money', MoneyElement);
customElements.define('x-credit', CreditElement);
customElements.define('x-form', FormElement);
customElements.define('x-blurhash', BlurHash, { extends: 'canvas' });
customElements.define('x-wave', WaveElement);
customElements.define('x-chart', ChartElement);
customElements.define('x-markdown', MarkdownElement);

// Define singletons for custom elements
window.modal = new ModalController();

// Set scrollbar width 
document.body.style.setProperty(`--scrollbar-width`, `0px`);

if (document.body.scrollHeight > document.body.clientHeight) {
    document.body.style.setProperty(`--scrollbar-width`, `${width}px`);
}