(function (wp) {
    'use strict';
    var data = window.brBrandsCatalogBlock;
    if (!data) { return; }
    var el = wp.element.createElement, __ = wp.i18n.__;
    var editor = wp.blockEditor || wp.editor;
    var components = wp.components;
    var ServerSideRender = (wp.serverSideRender && (wp.serverSideRender.default || wp.serverSideRender)) || components.ServerSideRender;
    wp.blocks.registerBlockType('berocket/brand-catalog', {
        apiVersion: data.apiVersion,
        title: __('BeRocket Brand Catalog', 'brands-for-woocommerce'),
        description: __('Display a catalogue of BeRocket brands without writing a shortcode.', 'brands-for-woocommerce'),
        icon: 'tag', category: 'widgets',
        keywords: ['BeRocket', __('brands', 'brands-for-woocommerce'), __('catalogue', 'brands-for-woocommerce')],
        attributes: data.attributes,
        supports: { html: false, customClassName: false },
        edit: function (props) {
            var a = props.attributes;
            var blockProps = editor.useBlockProps ? editor.useBlockProps() : { className: props.className };
            function set(name) { return function (value) { var next = {}; next[name] = value; props.setAttributes(next); }; }
            function toggle(name, label) {
                return el(components.ToggleControl, { label: label, checked: a[name], onChange: set(name) });
            }
            var categories = data.categories.slice();
            if (a.categoryId && !categories.some(function (item) { return Number(item.value) === a.categoryId; })) {
                categories.push({ value: String(a.categoryId), label: __('Unavailable category', 'brands-for-woocommerce') });
            }
            return el(wp.element.Fragment, null,
                el(editor.InspectorControls, null,
                    el(components.PanelBody, { title: __('Brand catalog settings', 'brands-for-woocommerce') },
                        el(components.SelectControl, { label: __('Product category', 'brands-for-woocommerce'), value: String(a.categoryId), options: categories,
                            help: __('Uses the categories assigned in each BeRocket brand’s settings.', 'brands-for-woocommerce'),
                            onChange: function (value) { set('categoryId')(Number(value)); } }),
                        el(components.SelectControl, { label: __('Group by', 'brands-for-woocommerce'), value: a.groupBy, onChange: set('groupBy'), options: [
                            { value: 'alphabet', label: __('Alphabet', 'brands-for-woocommerce') },
                            { value: 'category', label: __('Category', 'brands-for-woocommerce') },
                            { value: 'none', label: __('None', 'brands-for-woocommerce') }
                        ] }),
                        el(components.SelectControl, { label: __('Layout', 'brands-for-woocommerce'), value: a.layout, onChange: set('layout'), options: [
                            { value: 'vertical', label: __('Vertical', 'brands-for-woocommerce') },
                            { value: 'horizontal', label: __('Horizontal', 'brands-for-woocommerce') }
                        ] }),
                        el(components.RangeControl, { label: __('Columns', 'brands-for-woocommerce'), value: a.columns, min: 1, max: 6,
                            onChange: function (value) { set('columns')(Math.max(1, Math.min(6, Number(value) || 2))); } }),
                        toggle('showImages', __('Show logos', 'brands-for-woocommerce')),
                        toggle('showCounts', __('Show product counts', 'brands-for-woocommerce')),
                        toggle('hideEmpty', __('Hide brands with no products', 'brands-for-woocommerce')),
                        a.groupBy !== 'none' && toggle('showAll', __('Show “All” tab', 'brands-for-woocommerce'))
                    )
                ),
                el('div', blockProps, el('div', { className: 'br-brands-catalog-preview', 'data-first-only': a.groupBy !== 'none' && !a.showAll ? 'true' : 'false' },
                    el(components.Disabled, null, el(ServerSideRender, { block: 'berocket/brand-catalog', attributes: a }))
                ))
            );
        },
        save: function () { return null; }
    });
})(window.wp);
