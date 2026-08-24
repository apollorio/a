/**
 * Apollo Sheets — Gutenberg Block Editor Script
 *
 * Registers the client-side edit component for the "apollo-sheets/sheet" block.
 * Rendering is server-side (render.php), so this only provides the editor UI.
 *
 * Built with vanilla wp.blocks / wp.element — no bundler required for basic use.
 * For production builds replace with @wordpress/scripts build pipeline.
 */
(function (blocks, element, blockEditor, components, i18n) {
	'use strict';

	const { registerBlockType } = blocks;
	const { useBlockProps, InspectorControls } = blockEditor;
	const { PanelBody, TextControl, ToggleControl, RangeControl, SelectControl, Spinner } = components;
	const { __, _x } = i18n;
	const { useState, useEffect } = element;
	const el = element.createElement;

	registerBlockType('apollo-sheets/sheet', {
		edit: function (props) {
			const { attributes, setAttributes } = props;
			const blockProps = useBlockProps();

			const [preview, setPreview] = useState('');
			const [loading, setLoading] = useState(false);

			// Fetch server-side preview whenever id changes
			useEffect(function () {
				if (!attributes.id) {
					setPreview('');
					return;
				}
				setLoading(true);
				wp.ajax
					.post('apollo_sheets_preview', {
						_wpnonce: apolloSheetsBlock.nonce,
						sheet_id: attributes.id,
					})
					.done(function (res) {
						if (res && res.html) {
							setPreview(res.html);
						}
					})
					.always(function () {
						setLoading(false);
					});
			}, [attributes.id]);

			return el(
				'div',
				blockProps,
				// Inspector sidebar
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __('Sheet', 'apollo-sheets'), initialOpen: true },
						el(TextControl, {
							label: __('ID da Sheet', 'apollo-sheets'),
							value: attributes.id,
							onChange: function (v) { setAttributes({ id: v }); },
							help: __('Exemplo: 1', 'apollo-sheets'),
						})
					),
					el(
						PanelBody,
						{ title: __('DataTables', 'apollo-sheets'), initialOpen: false },
						el(ToggleControl, {
							label: __('Ativar DataTables', 'apollo-sheets'),
							checked: attributes.use_datatables,
							onChange: function (v) { setAttributes({ use_datatables: v }); },
						}),
						el(ToggleControl, {
							label: __('Ordenação', 'apollo-sheets'),
							checked: attributes.datatables_sort,
							onChange: function (v) { setAttributes({ datatables_sort: v }); },
						}),
						el(ToggleControl, {
							label: __('Filtro', 'apollo-sheets'),
							checked: attributes.datatables_filter,
							onChange: function (v) { setAttributes({ datatables_filter: v }); },
						}),
						el(ToggleControl, {
							label: __('Paginação', 'apollo-sheets'),
							checked: attributes.datatables_paginate,
							onChange: function (v) { setAttributes({ datatables_paginate: v }); },
						}),
						el(RangeControl, {
							label: __('Entradas por página', 'apollo-sheets'),
							value: attributes.paginate_entries,
							min: 1,
							max: 500,
							onChange: function (v) { setAttributes({ paginate_entries: v }); },
						})
					),
					el(
						PanelBody,
						{ title: __('Aparência', 'apollo-sheets'), initialOpen: false },
						el(SelectControl, {
							label: __('Cabeçalho', 'apollo-sheets'),
							value: attributes.table_head,
							options: [
								{ label: __('Nenhum', 'apollo-sheets'), value: '' },
								{ label: __('Primeira linha', 'apollo-sheets'), value: 'first_row' },
								{ label: __('Última linha (tfoot)', 'apollo-sheets'), value: 'last_row' },
								{ label: __('Primeira + Última', 'apollo-sheets'), value: 'both_rows' },
							],
							onChange: function (v) { setAttributes({ table_head: v }); },
						}),
						el(ToggleControl, {
							label: __('Cores alternadas', 'apollo-sheets'),
							checked: attributes.alternating_colors,
							onChange: function (v) { setAttributes({ alternating_colors: v }); },
						}),
						el(ToggleControl, {
							label: __('Responsivo', 'apollo-sheets'),
							checked: attributes.responsive,
							onChange: function (v) { setAttributes({ responsive: v }); },
						}),
						el(TextControl, {
							label: __('Larguras de colunas (pipe-separado)', 'apollo-sheets'),
							value: attributes.column_widths,
							onChange: function (v) { setAttributes({ column_widths: v }); },
							help: __('Ex: 100px|200px||150px', 'apollo-sheets'),
						})
					),
					el(
						PanelBody,
						{ title: __('Avançado', 'apollo-sheets'), initialOpen: false },
						el(ToggleControl, {
							label: __('Avaliar fórmulas', 'apollo-sheets'),
							checked: attributes.evaluate_formulas,
							onChange: function (v) { setAttributes({ evaluate_formulas: v }); },
						}),
						el(ToggleControl, {
							label: __('Converter quebras de linha', 'apollo-sheets'),
							checked: attributes.convert_line_breaks,
							onChange: function (v) { setAttributes({ convert_line_breaks: v }); },
						}),
						el(TextControl, {
							label: __('Linhas visíveis (ex: 1,3,5-10)', 'apollo-sheets'),
							value: attributes.show_rows,
							onChange: function (v) { setAttributes({ show_rows: v }); },
						}),
						el(TextControl, {
							label: __('Colunas ocultas (ex: 1,3)', 'apollo-sheets'),
							value: attributes.hide_columns,
							onChange: function (v) { setAttributes({ hide_columns: v }); },
						}),
						el(TextControl, {
							label: __('Classe CSS extra', 'apollo-sheets'),
							value: attributes.extra_css,
							onChange: function (v) { setAttributes({ extra_css: v }); },
						})
					)
				),
				// Canvas area
				loading
					? el(Spinner, null)
					: attributes.id
					? preview
						? el('div', {
								key: 'preview',
								dangerouslySetInnerHTML: { __html: preview },
							})
						: el('p', { style: { color: '#888', padding: '1em' } }, __('Carregando preview…', 'apollo-sheets'))
					: el(
							'div',
							{
								style: {
									border: '1px dashed #ccc',
									padding: '2em',
									textAlign: 'center',
									color: '#aaa',
								},
							},
							el('span', { className: 'dashicons dashicons-grid-view', style: { fontSize: '2em', display: 'block', marginBottom: '.5em' } }),
							__('Apollo Sheet — informe o ID da Sheet no painel lateral.', 'apollo-sheets')
						)
			);
		},

		// save: null means output is server-rendered
		save: function () {
			return null;
		},
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
