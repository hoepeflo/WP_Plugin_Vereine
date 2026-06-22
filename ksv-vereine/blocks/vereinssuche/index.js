(function (blocks, element, blockEditor, components, i18n) {
    const el = element.createElement;
    const { registerBlockType } = blocks;
    const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = blockEditor;
    const { PanelBody, TextControl, TextareaControl, ToggleControl, Button } = components;
    const { __ } = i18n;

    registerBlockType('ksv/vereinssuche', {
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps({
                className: 'ksv-vereine-editor-placeholder--vereinssuche',
            });

            return el(
                'div',
                blockProps,
                el(InspectorControls, {},
                    el(PanelBody, { title: __('Inhalt', 'ksv-vereine'), initialOpen: true },
                        el(TextControl, {
                            label: __('Überschrift', 'ksv-vereine'),
                            value: attributes.heading,
                            onChange: function (value) {
                                setAttributes({ heading: value });
                            },
                        }),
                        el(TextareaControl, {
                            label: __('Beschreibung', 'ksv-vereine'),
                            value: attributes.description,
                            onChange: function (value) {
                                setAttributes({ description: value });
                            },
                        }),
                        el(TextControl, {
                            label: __('Vereinsseite (URL, optional)', 'ksv-vereine'),
                            help: __('Leer lassen, um die Einstellung aus Vereine → Einstellungen zu verwenden.', 'ksv-vereine'),
                            value: attributes.vereinePageUrl,
                            onChange: function (value) {
                                setAttributes({ vereinePageUrl: value });
                            },
                        }),
                        el(ToggleControl, {
                            label: __('Link „Zur Vereinssuche“ anzeigen', 'ksv-vereine'),
                            checked: attributes.showLink,
                            onChange: function (value) {
                                setAttributes({ showLink: value });
                            },
                        })
                    ),
                    el(PanelBody, { title: __('Kartengrafik', 'ksv-vereine'), initialOpen: false },
                        el(MediaUploadCheck, {},
                            el(MediaUpload, {
                                onSelect: function (media) {
                                    setAttributes({
                                        imageId: media.id,
                                        imageUrl: media.url || '',
                                    });
                                },
                                allowedTypes: ['image'],
                                value: attributes.imageId,
                                render: function (obj) {
                                    return el(Button, {
                                        onClick: obj.open,
                                        variant: 'secondary',
                                    }, attributes.imageId
                                        ? __('Kartengrafik ersetzen', 'ksv-vereine')
                                        : __('Kartengrafik wählen', 'ksv-vereine'));
                                },
                            })
                        ),
                        attributes.imageId
                            ? el(Button, {
                                isDestructive: true,
                                variant: 'link',
                                onClick: function () {
                                    setAttributes({ imageId: 0, imageUrl: '' });
                                },
                            }, __('Standardgrafik verwenden', 'ksv-vereine'))
                            : null
                    )
                ),
                el('strong', {}, __('KSV Vereinssuche', 'ksv-vereine')),
                el('p', {}, attributes.heading || __('Schützenverein in Ihrer Nähe finden', 'ksv-vereine')),
                el('p', {}, __('Vorschau im Frontend', 'ksv-vereine'))
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
