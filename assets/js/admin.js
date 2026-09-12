/**
 * JavaScript for the Serbian Transliteration Plugin
 *
 * @link              http://infinitumform.com/
 * @since             1.0.1
 * @package           Serbian_Transliteration
 * @autor             Ivijan-Stefan Stipic
 */
;(function(){
	
	/*
	 * Fix special characters
	 */
	var decodeHtmlCharCodes = function decodeHtmlCharCodes(string, quoteStyle) { 
		//       discuss at: https://locutus.io/php/htmlspecialchars_decode/
		//      original by: Mirek Slugen
		//      improved by: Kevin van Zonneveld (https://kvz.io)
		//      bugfixed by: Mateusz "loonquawl" Zalega
		//      bugfixed by: Onno Marsman (https://twitter.com/onnomarsman)
		//      bugfixed by: Brett Zamir (https://brett-zamir.me)
		//      bugfixed by: Brett Zamir (https://brett-zamir.me)
		//         input by: ReverseSyntax
		//         input by: Slawomir Kaniecki
		//         input by: Scott Cariss
		//         input by: Francois
		//         input by: Ratheous
		//         input by: Mailfaker (https://www.weedem.fr/)
		//       revised by: Kevin van Zonneveld (https://kvz.io)
		// reimplemented by: Brett Zamir (https://brett-zamir.me)
		//        example 1: htmlspecialchars_decode("<p>this -&gt; &quot;</p>", 'ENT_NOQUOTES')
		//        returns 1: '<p>this -> &quot;</p>'
		//        example 2: htmlspecialchars_decode("&amp;quot;")
		//        returns 2: '&quot;'
		let optTemp = 0;
		let i = 0;
		let noquotes = false;
		if (typeof quoteStyle === 'undefined') {
			quoteStyle = 2;
		}
		string = string.toString()
			.replace(/&lt;/g, '<')
			.replace(/&gt;/g, '>');
		const OPTS = {
			ENT_NOQUOTES: 0,
			ENT_HTML_QUOTE_SINGLE: 1,
			ENT_HTML_QUOTE_DOUBLE: 2,
			ENT_COMPAT: 2,
			ENT_QUOTES: 3,
			ENT_IGNORE: 4
		}
		if (quoteStyle === 0) {
			noquotes = true;
		}
		if (typeof quoteStyle !== 'number') {
			// Allow for a single string or an array of string flags
			quoteStyle = [].concat(quoteStyle)
			for (i = 0; i < quoteStyle.length; i++) {
				// Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
				if (OPTS[quoteStyle[i]] === 0) {
					noquotes = true;
				} else if (OPTS[quoteStyle[i]]) {
					optTemp = optTemp | OPTS[quoteStyle[i]];
				}
			}
			quoteStyle = optTemp;
		}
		if (quoteStyle & OPTS.ENT_HTML_QUOTE_SINGLE) {
			// PHP doesn't currently escape if more than one 0, but it should:
			string = string.replace(/&#039;/g, "'");
			// This would also be useful here, but not a part of PHP:
			// string = string.replace(/&apos;|&#x0*27;/g, "'");
		}
		if (!noquotes) {
			string = string.replace(/&quot;/g, '"');
		}
		// Put this in last place to avoid escape being double-decoded
		string = string.replace(/&amp;/g, '&');
		return string;
	}

	
	/*
	 * AJAX request
	 */
	var xhttp_transient, xhttp_transient_timeout,
		ajax = (method, src, object, headers) => {
			if(xhttp_transient_timeout) clearTimeout(xhttp_transient_timeout);
			
			var xhttp = new XMLHttpRequest(), data = [], o=0;
			
			xhttp_transient = xhttp;
			
			xhttp_transient.onreadystatechange = () => {
				if (xhttp_transient.readyState == 4 && xhttp_transient.status == 200) {
					xhttp_transient_timeout = setTimeout(()=>{xhttp_transient = null;}, 3e3);
				}
			}
			
			xhttp.open(method, src, true);
			
			if(headers)
			{
				for(header in headers)
				{
					xhttp.setRequestHeader(header, headers[header]);
				}
			}
			
			if(object) {
				for(key in object)
				{
					data[o]=key + '=' + object[key];
					o++;
				}
				xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				xhttp.send(data.join('&', data));
			} else {
				xhttp.send();
			}			
		},
		ajax_anytime = (callback) => {
			xhttp_transient.onreadystatechange = () => callback(xhttp_transient);
		},
		ajax_done = (callback, is_json) => {
			if(!xhttp_transient) return;
			xhttp_transient.onreadystatechange = () => {
				if (xhttp_transient.readyState == 4 && xhttp_transient.status == 200) {
					if(is_json) {
						callback(JSON.parse(xhttp_transient.responseText), xhttp_transient);
					} else {
						callback(xhttp_transient.responseText, xhttp_transient);
					}
				}
			}
		},
		ajax_error = (callback) => {
			if(!xhttp_transient) return;
			xhttp_transient.onreadystatechange = () => {
				if (typeof xhttp_transient.readyState == 'undefined' || xhttp_transient.status != 200) {
					callback(xhttp_transient.status, xhttp_transient);
				}
			}
		};
	
	/* Display mode info */
	(function(mode, info){
		var info = document.getElementById(info),
			options = document.getElementsByName(mode),
			filters = document.getElementById('rstr-filter-mode-options'),
			i;
			
		if (options, info) {
			for (i = 0; i < options.length; i++) {
				if (options[i].checked){
					 if(options[i].value == 'forced'){
						info.style.display = null;
					} else {
						info.style.display = 'none';
					}
				}
			}
			
			document.addEventListener('input',(e)=>{
				if(e.target.getAttribute('name') === mode) {
					if(e.target.value == 'forced'){
						info.style.display = null;
					} else {
						info.style.display = 'none';
					}
					
					ajax('POST', RSTR.ajax, {
						'action' : 'rstr_filter_mode_options',
						'nonce'  : e.target.dataset.nonce,
						'mode' : e.target.value,
						'rstr_skip' : true
					}, {
						'Accept' : 'text/html'
					});
					
					filters.innerHTML = '<div class="col"><b style="color:#cc0000;">' + RSTR.label.loading + '</b></div>';
					
					ajax_done(function(data){
						filters.innerHTML = data;
					});
				}
			});
		}
	}('serbian-transliteration[mode]', 'forced-transliteration'));



	/*
	 * TOOLS: Transliterator
	 */
	(function(button, textarea, result){
		button = document.getElementsByClassName(button);
		
		if( button )
		{
			var transliterator_timeout;
			
			textarea = document.getElementById(textarea);
			result = document.getElementById(result);
			
			for(var i = 0; i < button.length; i++) {
				(function(index) {
					button[index].addEventListener("click", () => {
						
						if(transliterator_timeout) clearTimeout(transliterator_timeout);
						
						result.value = RSTR.label.loading;
													
						ajax('POST', RSTR.ajax, {
							'action' : 'rstr_transliteration_letters',
							'mode'   : button[index].dataset.mode,
							'nonce'  : button[index].dataset.nonce,
							'value'  : textarea.value,
							'rstr_skip' : true
						}, {
							'Accept' : 'text/plain'
						});
						
						ajax_done(function(data){
							result.value = decodeHtmlCharCodes(data);
							if(transliterator_timeout) clearTimeout(transliterator_timeout);
						});
						
						transliterator_timeout = setTimeout(function(){
							result.value = ' ';
						},1e4);
					})
				})(i);
			}
		}
	}('button-transliteration-letters', 'rstr-transliteration-letters', 'rstr-transliteration-letters-result'));




	/*
	 * TOOLS: Transliterate permalinks
	 */
	(function () {
		const apply = document.getElementById('serbian-transliteration-tools-transliterate-permalinks');
		if (!apply) return;
		const dry = document.getElementById('rstr-permalink-dry-run');
		const confirm = document.getElementById('serbian-transliteration-tools-check');
		const progress = document.getElementById('rstr-progress-bar');
		const result = document.getElementById('rstr-permalink-result');
		const preview = document.getElementById('rstr-permalink-preview');
		const resume = document.getElementById('rstr-permalink-resume');
		const reset = document.getElementById('rstr-permalink-reset');
		const csv = document.getElementById('rstr-permalink-csv');
		const report = document.getElementById('rstr-permalink-report');
		const selectors = Array.from(document.querySelectorAll('.tools-transliterate-permalinks-post-types, .tools-transliterate-permalinks-taxonomies'));
		const storageKey = 'rstr-permalinks:' + RSTR.permalink_storage;
		let pending = null;
		let busy = false;
		try { pending = JSON.parse(localStorage.getItem(storageKey)); } catch (error) { /* Storage can be disabled. */ }
		if (pending && (!pending.job || typeof pending.step !== 'number' || !pending.mode)) {
			pending = null;
			try { localStorage.removeItem(storageKey); } catch (error) { /* Storage can be disabled. */ }
		}
		resume.hidden = !pending;
		reset.hidden = !pending;
		function save() {
			try { localStorage.setItem(storageKey, JSON.stringify(pending)); } catch (error) { /* Retry still works in this page. */ }
		}
		function notice(message, error) {
			result.hidden = false;
			result.className = 'notice inline ' + (error ? 'notice-error' : 'notice-info');
			result.querySelector('p').textContent = message;
		}
		function controls(running) {
			busy = running;
			if (!running && pending && pending.expires && pending.expires * 1000 <= Date.now()) {
				pending = null;
				save();
				resume.hidden = true;
				reset.hidden = true;
			}
			const unfinished = pending && !pending.done;
			dry.disabled = running || unfinished;
			apply.disabled = running || unfinished || !confirm.checked;
			confirm.disabled = running;
			selectors.forEach(input => { input.disabled = running; });
			resume.disabled = running;
			reset.disabled = running;
		}
		confirm.addEventListener('change', () => {
			apply.disabled = busy || (pending && !pending.done) || !confirm.checked;
			document.getElementById('rstr-disclaimer').style.display = 'block';
		});
		function render(data) {
			const percent = Math.round(data.percentage);
			progress.style.display = 'block';
			progress.querySelector('.progress-value').style.width = percent + '%';
			progress.querySelector('.progress-value').dataset.value = percent;
			progress.querySelector('progress').value = percent;
			progress.querySelector('.progress-bar span').style.width = percent + '%';
			progress.querySelector('.progress-bar span').textContent = percent + '%';
			progress.querySelector('.progress-message').textContent = data.message;
			let summary = RSTR.label.permalink_summary;
			[data.total, data.updated, data.redirects, data.issues].forEach((value, index) => {
				summary = summary.replace('%' + (index + 1) + '$s', String(value));
			});
			notice(data.message + ' ' + summary + (data.done && data.mode === 'apply' && !data.csv ? ' ' + RSTR.label.permalink_no_csv : ''), false);
			if (data.rows && data.rows.length) {
				preview.hidden = false;
				const body = preview.querySelector('tbody');
				body.textContent = '';
				data.rows.slice(-50).forEach(row => {
					const tr = document.createElement('tr');
					['object_type', 'object_id', 'post_type_or_taxonomy', 'name', 'old_slug', 'new_slug', 'old_url', 'new_url', 'status', 'url_status'].forEach(key => {
						const td = document.createElement('td');
						td.textContent = row[key] == null ? '' : String(row[key]);
						tr.appendChild(td);
					});
					body.appendChild(tr);
				});
			}
			csv.hidden = !data.csv;
			report.hidden = !data.report;
			if (data.csv) csv.href = data.csv;
			if (data.report) report.href = data.report;
		}
		async function run() {
			if (busy || !pending) return;
			controls(true);
			resume.hidden = false;
			reset.hidden = false;
			try {
				while (pending) {
					const controller = new AbortController();
					const timer = setTimeout(() => controller.abort(), 90000);
					let data;
					try {
						const response = await fetch(RSTR.ajax, {
							method: 'POST', credentials: 'same-origin', signal: controller.signal,
							headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
							body: new URLSearchParams(Object.assign({}, pending, { action: 'rstr_run_permalink_transliteration', nonce: apply.dataset.nonce, rstr_skip: '1' })).toString()
						});
						if (!response.ok) throw new Error(RSTR.label.permalink_error);
						data = await response.json();
					} finally { clearTimeout(timer); }
					if (!data || data.error || typeof data.step !== 'number') {
						if (data && data.resume) { pending = data.resume; save(); }
						throw new Error(data && data.message ? data.message : RSTR.label.permalink_error);
					}
					pending.step = data.step;
					pending.done = data.done;
					pending.expires = data.expires;
					save();
					render(data);
					if (data.done) { resume.hidden = true; reset.hidden = false; break; }
				}
			} catch (error) {
				notice((error.message || RSTR.label.permalink_error) + ' ' + RSTR.label.permalink_error, true);
			} finally { controls(false); }
		}
		function start(mode) {
			if (busy || (mode === 'apply' && !confirm.checked)) return;
			const postTypes = selectors.filter(input => input.checked && input.classList.contains('tools-transliterate-permalinks-post-types')).map(input => input.value);
			const taxonomies = selectors.filter(input => input.checked && input.classList.contains('tools-transliterate-permalinks-taxonomies')).map(input => input.value);
			if (!postTypes.length && !taxonomies.length) { notice(RSTR.label.permalink_empty, true); return; }
			const bytes = new Uint8Array(16);
			crypto.getRandomValues(bytes);
			pending = { job: Array.from(bytes, byte => byte.toString(16).padStart(2, '0')).join(''), step: 0, expires: Math.floor(Date.now() / 1000) + 86400, mode: mode, confirmed: mode === 'apply' ? '1' : '0', post_type: postTypes.join(','), taxonomy: taxonomies.join(',') };
			save();
			csv.hidden = true;
			report.hidden = true;
			preview.hidden = true;
			run();
		}
		dry.addEventListener('click', () => start('dry_run'));
		apply.addEventListener('click', () => start('apply'));
		resume.addEventListener('click', run);
		reset.addEventListener('click', () => {
			pending = null;
			try { localStorage.removeItem(storageKey); } catch (error) { /* Storage can be disabled. */ }
			resume.hidden = true;
			reset.hidden = true;
			controls(false);
		});
		controls(false);
	}());
	/* Accordion */
	(function(c){
		var acc = document.getElementsByClassName(c), i;
		if(acc) {
			for (i = 0; i < acc.length; i++) {
				acc[i].addEventListener("click", function () {
					this.classList.toggle("active");
					var panel = this.nextElementSibling;
					if (panel.style.display === "block") {
						panel.style.display = "none";
					} else {
						panel.style.display = "block";
					}
				});
			}
		}
	}("accordion-link"))
	
	
	console.log("%c\n\nHey, are you are developer? Cool!!!\n\nJoin our team:\n\n%chttps://github.com/InfinitumForm/serbian-transliteration\n\n", "color: #cc0000; font-size: x-large;", "color: #cc0000; font-size: 18px");
}());
