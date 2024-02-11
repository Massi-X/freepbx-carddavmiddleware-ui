/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

//declaration: new TimePicker(target log container, language array)
class MagicConfig {
	#popup;
	#startButton;
	#logContainer;
	#language;

	//call it to start the magic
	constructor(popup, language) {
		if (window.magicIsRunning)
			return;

		window.magicIsRunning = true;
		this.#language = language;
		this.#popup = popup;

		//disallow closing the popup
		this.#popup.dialog({
			beforeClose: () => false
		});

		this.#startButton = popup[0].querySelector('#magic_start'); //no check for existence
		this.#logContainer = popup[0].querySelector('#magic_pre_container'); //no check for existence

		this.#startButton.setAttribute('disabled', 'disabled'); //disable start button
		this.#logContainer.innerHTML = ''; //reset container

		this._step1(); //start
	}

	//create/update superfecta scheme and print message step 1
	_step1() {
		this.#logContainer.innerHTML += this.#language['JS_magic_step1'] + '\n';
		this.#logContainer.innerHTML += this.#language['JS_magic_step1_1'] + '\n';
		this.#logContainer.scrollIntoView(false); //scroll to bottom

		//send request
		fetch('ajax.php?' + new URLSearchParams({
			module: phonemiddleware['ajax_name'],
			command: 'superfectainit'
		}), [])
			.then(response => {
				return this._resp_handler(response);
			})
			.then(data => {
				this._step2(data);
			}, error => {
				this._error(error.error.message);
			});
	}

	//move superfecta scheme at top
	_step2(data) {
		if (data.status) {
			this.#logContainer.innerHTML += data.message + '\n';
			this.#logContainer.scrollIntoView(false); //scroll to bottom

			fetch('ajax.php?' + new URLSearchParams({
				module: phonemiddleware['ajax_name'],
				command: 'superfectareorder'
			}), []).then(response => {
				return this._resp_handler(response);
			}).then(data => {
				this._step3(data);
			}, error => {
				this._error(error.error.message);
			});
		}
		else this._error(data.message);
	}

	//enable superfecta regex_2
	_step3(data) {
		this.#logContainer.innerHTML += data.message + '\n'; //don't care about errors. For info why see Phonemiddleware.class.php
		this.#logContainer.scrollIntoView(false); //scroll to bottom

		let formData = new FormData();
		formData.append('data[]', 'Regular_Expressions_2');
		let options = {
			method: 'post',
			body: formData
		};

		fetch('ajax.php?' + new URLSearchParams({
			module: 'superfecta',
			command: 'update_sources',
			scheme: phonemiddleware['SUPERFECTA_SCHEME']
		}), options).then(response => {
			return this._resp_handler(response);
		}).then(data => {
			this._step4(data);
		}, error => {
			this._error(error.error.message);
		});
	}

	//setup superfecta regex_2
	_step4(data) {
		if (data.success) {
			this.#logContainer.innerHTML += this.#language['JS_magic_step1_2'] + '\n';
			this.#logContainer.scrollIntoView(false); //scroll to bottom

			//check if cnam URL starts with https. This has big implications and will probably make the superfecta lookup fail, so at least warn the user
			if (phonemiddleware['numberToCnamURL'].startsWith('https'))
				this.#logContainer.innerHTML += '<b class="bluetext">' + this.#language['JS_magic_SSL_warning'] + '</b>\n';

			let formData = new FormData();
			formData.append('URL', phonemiddleware['numberToCnamURL']);
			formData.append('Enable_SPAM_Match', 'on');
			phonemiddleware['SUPERFECTA_SCHEME_CONFIG'].forEach(elem => {
				switch (elem['key']) {
					case 'POST_Data':
						formData.append('POST_Data', elem['value']);
						break;
					case 'Regular_Expressions':
						formData.append('Regular_Expressions', elem['value']);
						break;
					case 'SPAM_Regular_Expressions':
						formData.append('SPAM_Regular_Expressions', elem['value']);
						break;
				}
			});
			let options = {
				method: 'post',
				body: formData
			};

			fetch('ajax.php?' + new URLSearchParams({
				module: 'superfecta',
				command: 'save_options',
				scheme: phonemiddleware['SUPERFECTA_SCHEME'],
				source: 'Regular_Expressions_2'
			}), options).then(response => {
				return this._resp_handler(response);
			}).then(data => {
				this._step5(data);
			}, error => {
				this._error(error.error.message);
			});
		}
		else this._error(data.message);
	}

	//setup outcnam and print message step 2
	_step5(data) {
		if (data.status) {
			this.#logContainer.innerHTML += this.#language['JS_magic_step1_3'] + '\n';
			this.#logContainer.innerHTML += '<b>' + this.#language['JS_magic_step1_4'] + '</b>\n';
			this.#logContainer.innerHTML += this.#language['JS_magic_step2'] + '\n';
			this.#logContainer.scrollIntoView(false); //scroll to bottom

			fetch('ajax.php?' + new URLSearchParams({
				module: phonemiddleware['ajax_name'],
				command: 'outcnamsetup'
			}), []).then(response => {
				return this._resp_handler(response);
			}).then(data => {
				this._step6(data);
			}, error => {
				this._error(error.error.message);
			});
		}
		else this._error(data.message);
	}

	//retrieve all inbound routes, iterate over them and print step 3
	_step6(data) {
		if (data.status) {
			this.#logContainer.innerHTML += data.message + '\n';
			this.#logContainer.innerHTML += this.#language['JS_magic_step3'] + '\n';
			this.#logContainer.innerHTML += this.#language['JS_magic_step3_1'] + '\n';
			this.#logContainer.scrollIntoView(false); //scroll to bottom

			fetch('ajax.php?' + new URLSearchParams({
				module: 'core',
				command: 'getJSON',
				jdata: 'allDID'
			}), []).then(response => {
				return this._resp_handler(response);
			}).then(async data => {
				if (Array.isArray(data))
					for (let i = 0; i < data.length; i++) {
						const res = await this._step7Recursive(data[i]);
						if (!res) {
							window.magicIsRunning = false;
							return;
						}
					}
				else
					this.#logContainer.innerHTML += '<b>' + this.#language['JS_magic_step3_notfound'] + '</b>\n';

				this.#logContainer.innerHTML += '<b>' + this.#language['JS_magic_completed'] + '</b>\n';

				//standard freepbx function to apply changes
				fpbx_reload();

				//re-enable everything
				window.magicIsRunning = false;
				this.#popup.dialog({
					beforeClose: () => true
				});
				this.#startButton.removeAttribute('disabled');
			}, error => {
				this._error(error.error.message);
			});
		}
		else this._error(data.message);
	}

	//apply changes to the current inbound route
	async _step7Recursive(route) {
		//decode first
		route['cidnum'] = decodeURIComponent(route['cidnum']);
		route['extension'] = decodeURIComponent(route['extension']);

		this.#logContainer.innerHTML += this.#language['JS_magic_step3_2']
			.replace('%cid', route['cidnum'] ? route['cidnum'] : this.#language['undefined'])
			.replace('%did', route['extension'] ? route['extension'] : this.#language['undefined']) + '\n';

		let options = {
			method: 'post',
			body: JSON.stringify(route)
		};

		const res = await fetch('ajax.php?' + new URLSearchParams({
			module: phonemiddleware['ajax_name'],
			command: 'inboundroutesetup'
		}), options).then(response => {
			return this._resp_handler(response);
		}).then(data => {
			if (data.status) {
				this.#logContainer.innerHTML += data.message + '\n';
				this.#logContainer.scrollIntoView(false); //scroll to bottom
				return true;
			}
			else {
				this._error(data.message);
				return false;
			}
		}, error => {
			this._error(error.error.message);
			return false;
		});

		return res;
	}

	//error handler: print message + disable lock
	_error(msg) {
		this.#logContainer.innerHTML += '<b style="color:red">' + msg + '</b>\n';
		this.#logContainer.innerHTML += '<b style="color:red">' + this.#language['JS_magic_error'] + '</b>\n';
		this.#logContainer.scrollIntoView(false); //scroll to bottom

		//re-enable everything
		window.magicIsRunning = false;
		this.#popup.dialog({
			beforeClose: () => true
		});
		this.#startButton.removeAttribute('disabled');
	}

	//response handler, convenient shortcut to reduce code
	_resp_handler(response) {
		if (!response.ok) {
			return response.json().then(json => {
				return Promise.reject(json);
			});
		} else
			return response.json();
	}
}