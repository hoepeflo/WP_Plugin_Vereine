(function (blocks, element) {
    const el = element.createElement;
    const { registerBlockType } = blocks;

    registerBlockType('ksv/vereine', {
        edit: function () {
            return el(
                'div',
                { className: 'ksv-vereine-editor-placeholder' },
                'KSV Vereine – Vorschau im Frontend'
            );
        },
        save: function () {
            return null;
        },
    });
})(window.wp.blocks, window.wp.element);
