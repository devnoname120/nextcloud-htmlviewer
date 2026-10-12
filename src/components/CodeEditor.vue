<!--
  - @copyright 2024 HtmlViewer
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the HtmlViewer App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="htmlviewer-code-editor" ref="container"></div>
</template>

<script>
    import {basicSetup, EditorView} from 'codemirror';
    import {Compartment, EditorState} from '@codemirror/state';
    import {html} from '@codemirror/lang-html';

    const theme = EditorView.theme(
        {
            '&'                                                                   : {
                height         : '100%',
                color          : 'var(--color-main-text)',
                backgroundColor: 'var(--color-main-background)'
            },
            '.cm-scroller, .cm-content, .cm-gutter'                               : {
                fontFamily: 'monospace'
            },
            '.cm-gutters'                                                         : {
                color          : 'var(--color-text-maxcontrast)',
                backgroundColor: 'var(--color-background-dark)',
                borderRight    : '1px solid var(--color-border)'
            },
            '.cm-activeLine, .cm-activeLineGutter'                                : {
                backgroundColor: 'var(--color-background-hover)'
            },
            '.cm-cursor'                                                          : {
                borderLeftColor: 'var(--color-main-text)'
            },
            '&.cm-focused > .cm-scroller > .cm-selectionLayer .cm-selectionBackground, .cm-selectionBackground': {
                backgroundColor: 'var(--color-primary-element-light)'
            }
        }
    );

    export default {
        props: {
            modelValue  : {
                type    : String,
                required: true
            },
            disabled    : {
                type   : Boolean,
                default: false
            },
            lineWrapping: {
                type   : Boolean,
                default: true
            }
        },
        emits: ['update:modelValue'],
        mounted() {
            this.editable = new Compartment();
            this.wrapping = new Compartment();
            this.currentValue = this.modelValue;
            this.view = new EditorView(
                {
                    parent: this.$refs.container,
                    state : EditorState.create(
                        {
                            doc       : this.modelValue,
                            extensions: [
                                basicSetup,
                                html(),
                                theme,
                                this.wrapping.of(this.lineWrapping ? EditorView.lineWrapping : []),
                                this.editable.of(EditorView.editable.of(!this.disabled)),
                                EditorView.updateListener.of((update) => {
                                    if(update.docChanged) {
                                        this.currentValue = update.state.doc.toString();
                                        this.$emit('update:modelValue', this.currentValue);
                                    }
                                })
                            ]
                        }
                    )
                }
            );
        },
        beforeUnmount() {
            this.view.destroy();
        },
        watch: {
            modelValue(value) {
                if(value === this.currentValue) {
                    return;
                }

                this.currentValue = value;
                this.view.dispatch({changes: {from: 0, to: this.view.state.doc.length, insert: value}});
            },
            disabled(value) {
                this.view.dispatch({effects: this.editable.reconfigure(EditorView.editable.of(!value))});
            },
            lineWrapping(value) {
                this.view.dispatch({effects: this.wrapping.reconfigure(value ? EditorView.lineWrapping : [])});
            }
        }
    };
</script>

<style scoped lang="scss">
.htmlviewer-code-editor {
    flex-grow : 1;
    min-height: 0;
    overflow  : hidden;
}
</style>
