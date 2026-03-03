if (typeof wp !== 'undefined' && wp.richText && wp.domReady) {
	wp.domReady(() => {
		const ToolbarButton =
			(wp.blockEditor && wp.blockEditor.RichTextToolbarButton)
				? wp.blockEditor.RichTextToolbarButton
				: (wp.editor ? wp.editor.RichTextToolbarButton : null);

		if (!ToolbarButton) {
			return;
		}

		const request = (mode, value, onChange) => {
			const content = wp.richText.toHTMLString({ value });

			jQuery.ajax({
				type: 'POST',
				url: RSTR_TOOL.ajax,
				dataType: 'json',
				data: {
					action: 'rstr_transliteration_letters',
					mode,
					nonce: RSTR_TOOL.nonce,
					value: content,
					rstr_skip: true
				}
			}).done(function (html) {
				const newContent = wp.richText.create({ html: html });
				onChange(newContent);
			});
		};

		wp.richText.registerFormatType('transliteration-tool/latin', {
			title: RSTR_TOOL.label.toLatin,
			tagName: 'mark',
			className: 'transliterate-to-latin',
			icon: 'editor-textcolor',
			edit({ isActive, value, onChange }) {
				return wp.element.createElement(ToolbarButton, {
					icon: 'editor-textcolor',
					title: RSTR_TOOL.label.toLatin,
					onClick: () => request('cyr_to_lat', value, onChange),
					isActive,
				});
			},
		});

		wp.richText.registerFormatType('transliteration-tool/cyrillic', {
			title: RSTR_TOOL.label.toCyrillic,
			tagName: 'mark',
			className: 'transliterate-to-cyrillic',
			icon: 'editor-textcolor',
			edit({ isActive, value, onChange }) {
				return wp.element.createElement(ToolbarButton, {
					icon: 'editor-textcolor',
					title: RSTR_TOOL.label.toCyrillic,
					onClick: () => request('lat_to_cyr', value, onChange),
					isActive,
				});
			},
		});
	});
}