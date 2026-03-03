(function(mce, $) {
	var ajaxRequest = null;
	
    mce.create('tinymce.plugins.transliteration_plugin', {
        init: function(editor, url) {
            // Adding a button for transliteration to Latin
            editor.addButton('transliterate_to_latin', {
                text: RSTR_TOOL.label.Latin,
                icon: false,
                onclick: function() {
                    var _this = this, content = editor.getContent({ format: 'html' });
					_this.disabled(true);
					
					if (ajaxRequest) {
						ajaxRequest.abort();
					}
					 
                    ajaxRequest = transliterateText('cyr_to_lat', content, function(transliteratedContent) {
                        editor.setContent(transliteratedContent);
						_this.disabled(false);
                    }).fail(function(){
						_this.disabled(false);
					});
                }
            });

            // Adding a button for transliteration to Cyrillic
            editor.addButton('transliterate_to_cyrillic', {
                text: RSTR_TOOL.label.Cyrillic,
                icon: false,
                onclick: function() {
                    var _this = this, content = editor.getContent({ format: 'html' });
					_this.disabled(true);
					
					if (ajaxRequest) {
						ajaxRequest.abort();
					}
					
                    ajaxRequest = transliterateText('lat_to_cyr', content, function(transliteratedContent) {
                        editor.setContent(transliteratedContent);
						_this.disabled(false);
                    }).fail(function(){
						_this.disabled(false);
					});
                }
            });
        }
    });

    // Registering the plugin
    mce.PluginManager.add('transliteration_plugin', mce.plugins.transliteration_plugin);

    // Function for text transliteration
    function transliterateText(mode, content, callback) {
        // AJAX call for transliteration
        return $.ajax({
            type: 'POST',
            url: RSTR_TOOL.ajax,
            data: {
                action: 'rstr_transliteration_letters',
                mode: mode,
                nonce: RSTR_TOOL.nonce,
                value: content,
                rstr_skip: true
            }
        }).done(function(data) {
			callback(decodeHtmlCharCodes(data));
		});
    }

    // Decoding special HTML characters
    function decodeHtmlCharCodes(str) {
        return str.replace(/&#(\d+);/g, function(match, dec) {
            return String.fromCharCode(dec);
        });
    }
})(tinymce, jQuery);