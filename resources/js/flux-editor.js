import { Editor } from "@tiptap/core";
import StarterKit from "@tiptap/starter-kit";
import Placeholder from "@tiptap/extension-placeholder";
import Underline from "@tiptap/extension-underline";
import Link from "@tiptap/extension-link";
import TextAlign from "@tiptap/extension-text-align";
import Highlight from "@tiptap/extension-highlight";
import Subscript from "@tiptap/extension-subscript";
import Superscript from "@tiptap/extension-superscript";
import Table from "@tiptap/extension-table";
import TableRow from "@tiptap/extension-table-row";
import TableCell from "@tiptap/extension-table-cell";
import TableHeader from "@tiptap/extension-table-header";

const defaultExtensions = {
    starterKit: StarterKit,
    placeholder: Placeholder,
    underline: Underline,
    link: Link,
    textAlign: TextAlign,
    highlight: Highlight,
    subscript: Subscript,
    superscript: Superscript,
};

const tableExtensions = {
    table: Table,
    tableRow: TableRow,
    tableCell: TableCell,
    tableHeader: TableHeader,
};

// Global registries for flux:editor event
const customExtensions = [];
const disabledExtensions = new Set();
const enabledTableExtensions = new Set();
const initCallbacks = [];

function buildExtensionList(placeholder) {
    const list = [];

    // StarterKit with defaults matching Flux Pro
    if (!disabledExtensions.has("starterKit")) {
        list.push(
            StarterKit.configure({
                heading: { levels: [1, 2, 3] },
                codeBlock: false,
            }),
        );
    }

    if (!disabledExtensions.has("placeholder")) {
        list.push(
            Placeholder.configure({
                placeholder: placeholder || "Escribe algo…",
            }),
        );
    }

    if (!disabledExtensions.has("underline")) list.push(Underline);
    if (!disabledExtensions.has("link")) {
        list.push(
            Link.configure({
                openOnClick: false,
                HTMLAttributes: { class: "text-blue-600 underline" },
            }),
        );
    }
    if (!disabledExtensions.has("textAlign")) {
        list.push(
            TextAlign.configure({
                types: ["heading", "paragraph"],
            }),
        );
    }
    if (!disabledExtensions.has("highlight"))
        list.push(Highlight.configure({ multicolor: false }));
    if (!disabledExtensions.has("subscript")) list.push(Subscript);
    if (!disabledExtensions.has("superscript")) list.push(Superscript);

    // Table extensions only if explicitly enabled (disabled by default per docs)
    for (const name of enabledTableExtensions) {
        const ext = tableExtensions[name];
        if (ext) {
            if (name === "table") list.push(ext.configure({ resizable: true }));
            else list.push(ext);
        }
    }

    // Custom registered extensions (replace if same name exists)
    for (const ext of customExtensions) {
        const idx = list.findIndex((e) => e.name === ext.name);
        if (idx !== -1) list.splice(idx, 1, ext);
        else list.push(ext);
    }

    return list;
}

function dispatchFluxEditorEvent() {
    const detail = {
        registerExtension(ext) {
            // replace existing
            const idx = customExtensions.findIndex((e) => e.name === ext.name);
            if (idx !== -1) customExtensions[idx] = ext;
            else customExtensions.push(ext);
        },
        registerExtensions(exts) {
            exts.forEach((e) => detail.registerExtension(e));
        },
        enableExtension(name) {
            disabledExtensions.delete(name);
            if (tableExtensions[name]) enabledTableExtensions.add(name);
        },
        disableExtension(name) {
            disabledExtensions.add(name);
            if (tableExtensions[name]) enabledTableExtensions.delete(name);
        },
        init(callback) {
            initCallbacks.push(callback);
        },
    };
    document.dispatchEvent(new CustomEvent("flux:editor", { detail }));
}

// Dispatch once on load so app.js listeners can register
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", dispatchFluxEditorEvent);
} else {
    dispatchFluxEditorEvent();
}

// Allow late listeners to still register by re-dispatching when they attach
// We monkey patch addEventListener for flux:editor to immediately call with current detail if already dispatched
const originalAddEventListener = document.addEventListener;
document.addEventListener = function (type, listener, options) {
    if (type === "flux:editor") {
        // wrap to provide same detail object
        const wrapped = (e) => listener(e);
        originalAddEventListener.call(this, type, wrapped, options);
        // immediately invoke with current state so late listeners work
        const detail = {
            registerExtension(ext) {
                const idx = customExtensions.findIndex(
                    (e) => e.name === ext.name,
                );
                if (idx !== -1) customExtensions[idx] = ext;
                else customExtensions.push(ext);
            },
            registerExtensions(exts) {
                exts.forEach((e) => {
                    const idx = customExtensions.findIndex(
                        (x) => x.name === e.name,
                    );
                    if (idx !== -1) customExtensions[idx] = e;
                    else customExtensions.push(e);
                });
            },
            enableExtension(name) {
                disabledExtensions.delete(name);
                if (tableExtensions[name]) enabledTableExtensions.add(name);
            },
            disableExtension(name) {
                disabledExtensions.add(name);
                if (tableExtensions[name]) enabledTableExtensions.delete(name);
            },
            init(callback) {
                initCallbacks.push(callback);
            },
        };
        // async to allow listener to be registered first
        queueMicrotask(() => {
            listener({ detail });
        });
        return;
    }
    return originalAddEventListener.call(this, type, listener, options);
};

function syncToLivewire(editor, el) {
    const html = editor.getHTML();
    // Find wire:model input (hidden textarea or input)
    const wireModelAttr = el.getAttribute("data-wire-model");
    const wireEl = el.querySelector("[data-flux-editor-input]");
    if (wireEl) {
        wireEl.value = html;
        wireEl.dispatchEvent(new Event("input", { bubbles: true }));
    }
    // Also update root value attribute for form submission
    el.setAttribute("value", html);
    el.value = html;
    el.dispatchEvent(
        new CustomEvent("flux:editor:change", {
            detail: { html },
            bubbles: true,
        }),
    );
}

window.fluxEditor = function (opts = {}) {
    return {
        editor: null,
        isMounted: false,
        init() {
            const el = this.$el;
            const contentEl =
                el.querySelector('[data-slot="content"]') ||
                el.querySelector("[data-flux-editor-content]");
            const inputEl = el.querySelector("[data-flux-editor-input]");
            const initialContent =
                opts.content ||
                inputEl?.value ||
                contentEl?.innerHTML ||
                el.getAttribute("value") ||
                "";

            const extensions = buildExtensionList(opts.placeholder);

            const editor = new Editor({
                element: contentEl,
                extensions,
                content: initialContent || "<p></p>",
                editable: !opts.disabled,
                onUpdate: ({ editor }) => {
                    syncToLivewire(editor, el);
                },
                onCreate: ({ editor }) => {
                    // Run init callbacks
                    initCallbacks.forEach((cb) => {
                        try {
                            cb({ editor });
                        } catch (e) {
                            console.error(
                                "[flux:editor] init callback failed",
                                e,
                            );
                        }
                    });
                },
            });

            this.editor = editor;
            this.isMounted = true;

            // Expose for toolbar buttons via Alpine scope
            el._fluxEditor = editor;
            el.editor = editor;
            el.value = editor.getHTML();
            // Ensure content element reflects editor's HTML for Livewire morph diff
            if (contentEl) contentEl._fluxEditor = editor;

            // Livewire -> editor sync: when hidden input changes externally, update tiptap
            if (inputEl) {
                const syncFromLivewire = () => {
                    const livewireHtml = inputEl.value;
                    if (livewireHtml !== editor.getHTML()) {
                        editor.commands.setContent(
                            livewireHtml || "<p></p>",
                            false,
                        );
                    }
                };
                // Listen to Livewire update events
                inputEl.addEventListener("flux:editor:sync", syncFromLivewire);
                // Morph/updated hooks
                el._fluxSyncFromLivewire = syncFromLivewire;
            }

            // Cleanup
            this.$el.addEventListener("destroy", () => editor.destroy());
        },
        destroy() {
            this.editor?.destroy();
        },
        // Exposed helpers for toolbar
        isActive(name, attrs = {}) {
            return this.editor?.isActive(name, attrs) ?? false;
        },
        canChain() {
            return this.editor?.can().chain().focus();
        },
    };
};

// Global Livewire sync: when Livewire patches DOM, reconcile hidden input -> editor content
function syncAllEditorsFromLivewire() {
    document.querySelectorAll("[data-flux-editor]").forEach((el) => {
        const editor =
            el._fluxEditor ||
            el.querySelector('[data-slot="content"]')?._fluxEditor;
        const inputEl = el.querySelector("[data-flux-editor-input]");
        if (editor && inputEl && inputEl.value !== editor.getHTML()) {
            // Avoid overwriting while user is typing (focus)
            const isFocused = editor.isFocused;
            if (!isFocused) {
                editor.commands.setContent(inputEl.value || "<p></p>", false);
            }
        }
    });
}

document.addEventListener("livewire:updated", syncAllEditorsFromLivewire);
document.addEventListener("livewire:update", syncAllEditorsFromLivewire);
document.addEventListener("livewire:morph-updated", syncAllEditorsFromLivewire);

// Also support vanilla init without Alpine for custom usages
window.initFluxEditor = function (rootEl, opts = {}) {
    const contentEl =
        rootEl.querySelector('[data-slot="content"]') ||
        rootEl.querySelector("[data-flux-editor-content]");
    if (!contentEl) return null;
    const extensions = buildExtensionList(
        opts.placeholder || rootEl.dataset.placeholder,
    );
    const editor = new Editor({
        element: contentEl,
        extensions,
        content: opts.content || rootEl.getAttribute("value") || "<p></p>",
        editable: !opts.disabled,
        onUpdate: ({ editor }) => syncToLivewire(editor, rootEl),
    });
    rootEl._fluxEditor = editor;
    initCallbacks.forEach((cb) => {
        try {
            cb({ editor });
        } catch {}
    });
    return editor;
};
