/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

//no check is done on the pm_language or other necessary variables. Please make sure they are present when including this file
document.addEventListener('DOMContentLoaded', () => {
	/********************	GET ELEMENTS	********************/
	$setupCarddav = $('#setupCarddav');
	carddav_url = document.getElementById('carddav_url');
	carddav_ssl_enable = document.getElementById('carddav_ssl_enable');
	carddav_user = document.getElementById('carddav_user');
	carddav_psw = document.getElementById('carddav_psw');
	carddav_validate = document.getElementById('carddav_validate');
	carddav_display_url = document.getElementById('carddav_display_url');
	carddav_result_tbody = document.getElementById('carddav_result').getElementsByTagName('tbody')[0];
	reducemovement = window.matchMedia('(prefers-reduced-motion: reduce)').matches; //it works only on reload. Well, it's fine

	//save initial values for carddav popup
	carddav_url_last = carddav_url.value;
	carddav_ssl_enable_last = carddav_ssl_enable.checked;
	carddav_user_last = carddav_user.value;
	carddav_psw_last = carddav_psw.value;

	/********************	START TAGIFY	********************/
	document.addEventListener('dragover', e => e.preventDefault());

	//tagify output_construct
	var output_construct = new Tagify(document.getElementById('output_construct'), {
		whitelist: [{
			"value": "fn",
			"name": `${pm_language['JS_fn']} (fn)`
		},
		{
			"value": "n", //kept "n" for backward compatibility
			"name": `${pm_language['Name']} (n)`
		},
		{
			"value": "nickname",
			"name": `${pm_language['Nickname']} (nickname)`
		},
		{
			"value": "bday",
			"name": `${pm_language['Birthday']} (bday)`
		},
		{
			"value": "adr",
			"name": `${pm_language['Address']} (adr)`
		},
		{
			"value": "email",
			"name": `${pm_language['JS_email_adr']} (email)`
		},
		{
			"value": "title",
			"name": `${pm_language['Title']} (title)`
		},
		{
			"value": "role",
			"name": `${pm_language['Role']} (role)`
		},
		{
			"value": "org",
			"name": `${pm_language['JS_org']} (org)`
		},
		],
		tagTextProp: 'name',
		dropdown: {
			mapValueTo: 'name',
			maxItems: Infinity
		},
		enforceWhitelist: true,
		backspace: false,
		userInput: false,
		originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(',')
	});

	//custom class for css styles
	output_construct.toggleClass('output_construct__tagify');

	//dragsort for output_construct
	new DragSort(output_construct.DOM.scope, {
		selector: '.' + output_construct.settings.classNames.tag,
		callbacks: {
			dragEnd: () => output_construct.updateValueByDOMTags()
		}
	});

	//generate whitelist for phone_type
	phone_type_whitelist = [];
	let i = 0;
	while (true) {
		let name = pm_language['PHONE_TYPE_' + i];
		if (name === undefined)
			break;
		phone_type_whitelist.push({ 'value': i.toString(), 'name': name }); //tagify doesn't play well with zero values, so convert everything to string
		++i;
	}

	//tagify phone_type
	var tagify = new Tagify(document.getElementById('phone_type'), {
		enforceWhitelist: true,
		whitelist: phone_type_whitelist,
		mode: "select",
		backspace: false,
		userInput: false,
		tagTextProp: 'name',
		dropdown: {
			mapValueTo: 'name',
			maxItems: Infinity
		},
		originalInputValueFormat: valuesArr => valuesArr.map(item => item.value)
	});

	//custom class for css styles
	tagify.toggleClass('phone_type__tagify');

	//tagify county_code
	tagify = new Tagify(document.getElementById('country_code'), {
		enforceWhitelist: true,
		whitelist: phonemiddleware['country_codes'],
		callbacks: {
			remove: e => {
				e.detail.tagify['valueBeforeRemove'] = e.detail.data.value;
			},
			blur: e => {
				if (e.detail.tagify.value.length === 0) {
					e.detail.tagify.state.dropdown.visible = false; //prevent dropdown from rifocusing everything up
					e.detail.tagify.addTags(e.detail.tagify['valueBeforeRemove']);
				}
			},
		},
		mode: "select",
		tagTextProp: 'name',
		dropdown: {
			mapValueTo: 'name',
			searchKeys: ['name', 'value'],
			maxItems: Infinity
		},
		originalInputValueFormat: valuesArr => valuesArr.map(item => item.value)
	});
	/********************	END TAGIFY	********************/

	/********************	CARDDAV VALIDATION & SAVE	********************/
	//listener for sortable to set element width on window resize
	window.resizeWidth = window.innerWidth;
	window.addEventListener('resize', () => {
		clearTimeout(window.resizeTimer);
		window.resizeTimer = setTimeout(() => {
			if (window.innerWidth !== window.resizeWidth) {
				window.resizeWidth = window.innerWidth;
				sortableListResize();
			}
		}, 50);
	});
	/********************	END CARDDAV VALIDATION & SAVE	********************/

	/********************	NOTIFICATION UI	********************/
	notificationHeader = document.getElementById('notification-header');
	notificationUI = document.getElementById('notification-ui');
	notificationCount = document.getElementById('notification-count');

	//close notifications on click outside
	window.addEventListener('click', ({ target }) => {
		const popup = target.closest('#notification-ui');

		if (notificationUI.classList.contains('open') && popup == null)
			toggleNotification(true);
	});

	//parse timestamp into date-time
	document.querySelectorAll('.notification-timestamp').forEach(elem => {
		let timestamp = elem.innerHTML * 1000; //convert to millisecond
		var date = new Date(timestamp);

		elem.innerHTML = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
	});
	/********************	END NOTIFICATION UI	********************/

	/********************	INIT POPUPS	********************/
	$('#welcomePopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement,
		dialogClass: 'no-close-btn',
		closeOnEscape: false,
		buttons: [
			{
				text: pm_language['Yes'],
				click: initTour
			},
			{
				text: pm_language['Donotshowagain'],
				click: function () {
					$(this).dialog("close");
					setFirstRun();
				}
			}
		]
	});

	$setupCarddav.dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement,
		closeOnEscape: false,
		open: openSetup,
		close: restoreCarddav
	});

	$('#errorPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement
	});

	$('#licensePopupUI').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement
	});

	$('#licensePopupCore').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement
	});

	$('#librariesPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement
	});

	window.magicPopup = $('#magicPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto',
		show: !reducemovement,
		hide: !reducemovement
	});

	//only load welcome popup if this is the first run
	if (phonemiddleware['isFirstRun'])
		$('#welcomePopup').dialog('open');
	/********************	END INIT POPUPS	********************/

	/********************	LISTEN MAX_CNAM_OUTPUT CHECKBOX	********************/
	let max_cnam_length = document.getElementById('max_cnam_length');
	let max_cnam_length_enable = document.getElementById('max_cnam_length_enable');

	max_cnam_length.onblur = e => {
		let elem = e.target;
		let min = parseInt(elem.getAttribute('min'));
		let max = parseInt(elem.getAttribute('max'));

		if (!min && !max)
			return;

		if (elem.value < min || elem.value > max) {
			if (elem.value < min)
				elem.value = min;
			else if (elem.value > max)
				elem.value = max;

			elem.classList.add('input-invalid-blink');
			setTimeout(() => elem.classList.remove('input-invalid-blink'), 1000);
		}
	};

	max_cnam_length_enable.onchange = () => {
		max_cnam_length_enable.checked ? max_cnam_length.removeAttribute('disabled') : max_cnam_length.setAttribute('disabled', 'disabled');
		max_cnam_length.value = max_cnam_length.getAttribute('min');
	}
	/********************	END LISTEN MAX_CNAM_OUTPUT CHECKBOX	********************/

	/********************	LISTEN XML_AUTH INPUTS	********************/
	let xml_auth_user = document.getElementById('xml_auth_user');
	let xml_auth_psw = document.getElementById('xml_auth_psw');

	xml_auth_user.oninput = e => {
		e.target.value = e.target.value.replace(/[^A-Za-z0-9]/g, ''); //disallow special chars
		if (e.target.value.length == 0) xml_auth_psw.value = ''; //make psw input blank if user is
	};

	xml_auth_psw.oninput = e => e.target.value.length == 0 ? xml_auth_user.value = '' : null; //make user input blank if psw is
	/********************	END LISTEN XML_AUTH INPUTS	********************/


	/********************	MISC	********************/
	//enable timepicker on cache_expire
	new TimePicker(document.getElementById('cache_expire'), 15);

	//update notification title text
	updateNotificationText();

	//tabindex helper
	document.querySelectorAll('.radioset > label').forEach(elem => {
		elem.onkeydown = e => {
			if (e.key === 'Enter')
				elem.click();
		};
	});

	//disallow spaces inside addressbook dialog inputs
	carddav_url.oninput = carddav_user.oninput = carddav_psw.oninput = e => e.target.value = e.target.value.replace(/\s/g, '');
}, false);

//prevent form submission with enter key
window.addEventListener('keydown', e => {
	if (e.key == 'Enter' && e.target.nodeName == 'INPUT') {
		e.preventDefault();
		return false;
	}
}, true);

/********************	CARDDAV VALIDATION & SAVE	********************/
let carddavController = new AbortController();

function validateCarddav() {
	carddavController = new AbortController(); //reset
	let isSave = false;
	let checkboxes = document.querySelectorAll('input[type=checkbox][name="carddav_addressbooks[]"]'); //this changes at every request
	let request_type = `ajax.php?module=${phonemiddleware['ajax_name']}&command=`;
	let formData = new FormData();
	let options = {
		method: 'post',
		body: formData,
		signal: carddavController.signal
	};

	checkboxes.forEach(elem => { //determine if this is save
		if (elem.checked) {
			isSave = true;
			return;
		}
	});

	//construct base formdata request
	formData.append('carddav_url', carddav_url.value);
	formData.append('carddav_ssl_enable', carddav_ssl_enable.checked ? carddav_ssl_enable.value : '');
	formData.append('carddav_user', carddav_user.value);
	formData.append('carddav_psw', carddav_psw.value);

	if (isSave) {
		carddav_validate.innerHTML = `<i class="fa fa-spinner letsspin"></i> ${pm_language['Saving_dots']}`;
		request_type += 'savecarddav';

		for (let i = 0; i < checkboxes.length; ++i) //add checked URLs to request
			if (checkboxes[i].checked)
				formData.append('carddav_addressbooks[]', checkboxes[i].getAttribute('data-uri'));
	} else {
		if (carddav_result_tbody.classList.contains('ui-sortable'))
			$(carddav_result_tbody).sortable("destroy"); //detach sortable before updating content
		carddav_validate.innerHTML = `<i class="fa fa-spinner letsspin"></i> ${pm_language['Validating_dots']}`;
		carddav_result_tbody.innerHTML = `<tr><td colspan="4">${pm_language['Loading_dots']}</td></tr>`;
		request_type += 'validatecarddav';
	}

	//disable every input
	carddav_validate.setAttribute('disabled', 'disabled');
	carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = carddav_ssl_enable.disabled = 'disabled';

	//send request
	fetch(request_type, options) //absolute path to www folder SAME AS UNINSTALL.PHP AND INSTALL.PHP
		.then(response => {
			if (!response.ok) {
				return response.json().then(json => {
					return Promise.reject(json);
				});
			} else
				return response.json();
		})
		.then(data => {
			if (isSave) {
				carddav_display_url.value = carddav_url.value; //update main interface URL

				//update saved inputs values
				carddav_url_last = carddav_url.value;
				carddav_ssl_enable_last = carddav_ssl_enable.checked;
				carddav_user_last = carddav_user.value;
				carddav_psw_last = carddav_psw.value;

				$setupCarddav.dialog('close'); //close popup
			} else {
				carddav_result_tbody.innerHTML = ''; //reset content

				if (data.length == 0) //if nothing is found
					carddav_result_tbody.innerHTML = `<tr><td colspan="4" class="carddav_error"><b>${pm_language['No_addresbook_found']}</b></td></tr>`;
				else {
					data.forEach(item => {
						carddav_result_tbody.innerHTML += '<tr>' +
							`<td><i class="fa fa-bars ui-sortable-handle" title="${pm_language['Move']}"></i></td>` + //move
							`<td><label class="ph-checkbox small"><input type="checkbox" ${item['checked'] ? 'checked' : ''} ' name="carddav_addressbooks[]" onclick="changeCarddavButton()" data-uri="${item['uri']}"><span data-info="custom-checkbox"></span></label></td>` + //checkbox
							`<td>${item['name']}</td>` + //name
							`<td>${item['uri']}</td>` + //url
							'</tr>';
					});
					sortableListResize();
					initSortableList();
				}
			}
			changeCarddavButton();
		}, error => {
			if (isSave) //only if there is an error during save alert the user
				alert(error.error.message);
			else {
				if (error.error === undefined) return; //this happens only when abort()
				carddav_result_tbody.innerHTML = `<tr><td colspan="4" class="carddav_error"><b>${error.error.message}</b></td></tr>`; //else we only show a generic no address book found inside the table
			}

			changeCarddavButton();
		});
}

//catch dialogopen to load saved values in the table and init sortable list
function openSetup() {
	validateCarddav();
	sortableListResize(); //listener for sortable to set element width on dialog open
}

//restore last values before popup closes. This gets called after a save, but it doesn't matter as the values are already saved when this is invoked
function restoreCarddav() {
	carddav_url.value = carddav_url_last;
	carddav_ssl_enable.checked = carddav_ssl_enable_last;
	carddav_user.value = carddav_user_last;
	carddav_psw.value = carddav_psw_last;

	carddav_result_tbody.innerHTML = `<tr><td colspan="4">${pm_language['Loading_dots']}</td></tr>`; //it's mandatory to restore the body of results
	carddavController.abort(); //and abort, too
}

//this changes the button according to the current state
function changeCarddavButton() {
	carddav_validate.innerHTML = pm_language['Validate'];
	carddav_validate.removeAttribute('disabled');
	carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = carddav_ssl_enable.disabled = '';

	document.querySelectorAll('input[type=checkbox][name="carddav_addressbooks[]"]').forEach(elem => { //this change at every request
		if (elem.checked) {
			carddav_validate.innerHTML = pm_language['Save'];
			carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = carddav_ssl_enable.disabled = 'disabled';
		}
	});
}

//toggle SSL button and update table
function toggleSSL(elem) {
	let target = document.querySelector('*[data-toggled-by="carddav_ssl_enable"]');
	target.classList.remove('greentext', 'redtext');

	if (elem.checked) {
		target.classList.add('greentext')
		target.innerHTML = pm_language['SSL_Active'];
	} else {
		target.classList.add('redtext');
		target.innerHTML = pm_language['SSL_Bypass'];
	}

	carddav_result_tbody.innerHTML = `<tr><td colspan="4" class="carddav_error"><b>${pm_language['Must_validate']}</b></td></tr>`;
}

//init sortable UI on carddav addressbooks
function initSortableList() {
	$(carddav_result_tbody).sortable({
		//fix row width unwanted shrink
		start: (event, ui) => {
			//thanks to someone on SO for this (sorry lost the link!)
			var cellCount = 0;
			$('td, th', ui.helper).each(() => {
				var colspan = 1;
				var colspanAttr = $(this).attr('colspan');
				if (colspanAttr > 1)
					colspan = colspanAttr;
				cellCount += colspan;
			});
			ui.placeholder.html(`<td colspan="${cellCount}">&nbsp;</td>`);

			//fix table "jump"
			var height = ui.helper.outerHeight();
			ui.placeholder.height(height);
		},
		containment: "parent", //prevent the row from going outside the table
		axis: 'y', //only y movement
		tolerance: "pointer", //pointer on other row will trigger the switch
		revert: 100, //animation when drag ends
		handle: '.fa' //self explains
	}).disableSelection();
}

//handle window resize + initialization to avoid jumps when moving rows
function sortableListResize() {
	document.querySelectorAll('#carddav_result th, #carddav_result td').forEach(elem => {
		elem.style.width = '';
	});
	document.querySelectorAll('#carddav_result th, #carddav_result td').forEach(elem => {
		elem.style.width = elem.offsetWidth + 'px';
	});
	document.querySelectorAll('#carddav_result tr').forEach(elem => {
		elem.style.height = '';
	});
	document.querySelectorAll('#carddav_result tr').forEach(elem => {
		elem.style.height = elem.offsetHeight + 'px';
	});
}
/********************	END CARDDAV VALIDATION & SAVE	********************/

/********************	NOTIFICATION UI	********************/
function toggleNotification(close) { //open/close notifications
	if (close === undefined)
		close = notificationUI.classList.contains('open');

	if (close) {
		if (!notificationUI.classList.contains('open'))
			return;

		notificationUI.classList.toggle('open');
	}
	else /* if (open) */ {
		if (parseInt(notificationCount.getAttribute('data-count')) == 0 || notificationUI.classList.contains('open'))
			return;

		notificationUI.classList.toggle('open');
	}
}

function updateNotificationText(reset, decrementValue) { //update title text according to current count
	if (decrementValue === undefined)
		decrementValue = 0;

	let notificationTotal = parseInt(notificationCount.getAttribute('data-count'));
	if (reset)
		notificationTotal = 0;
	else if (decrementValue < 0)
		notificationTotal += decrementValue;

	notificationCount.setAttribute('data-count', notificationTotal);
	notificationHeader.classList.add('bell-shake');
	notificationCount.innerHTML = notificationTotal;

	if (notificationTotal == 0) {
		notificationHeader.classList.remove('bell-shake');
		toggleNotification(true); //close notification UI if count == 0
	} else if (notificationTotal > 9)
		notificationCount.innerHTML = '9+';
}

function deleteNotification(elem) { //delete notification
	elem.setAttribute('disabled', 'disabled'); //instantly disable button to prevent multiple presses
	let formData = new FormData(); //no confirm

	formData.append('id', elem.getAttribute('data-notificationid'));

	let options = {
		method: 'post',
		body: formData
	};

	//send request
	fetch('ajax.php?' + new URLSearchParams({
		module: phonemiddleware['ajax_name'],
		command: 'deletenotification'
	}), options).then(response => {
		if (!response.ok) {
			return response.json().then(json => {
				return Promise.reject(json);
			});
		} else
			return response.json();
	}).then(() => {
		elem.closest('.notification-bubble').remove();
		updateNotificationText(false, -1);
	}, error => {
		elem.removeAttribute('disabled'); //let the user retry
		alert(error.error.message);
	});
}

function deleteAllNotifications() { //delete ALL notifications
	if (!confirm(pm_language['JS_confirm_delete_notifications']))
		return;

	let formData = new FormData();
	let options = {
		method: 'post',
		body: formData
	};

	//send request
	fetch('ajax.php?' + new URLSearchParams({
		module: phonemiddleware['ajax_name'],
		command: 'deleteallnotifications'
	}), options).then(response => {
		if (!response.ok) {
			return response.json().then(json => {
				return Promise.reject(json);
			});
		} else
			return response.json();
	}).then(() => {
		document.querySelectorAll('.notification-bubble').forEach(elem => {
			elem.remove();
		});
		updateNotificationText(true);
	}, error => {
		alert(error.error.message);
	});
}
/********************	END NOTIFICATION UI	********************/

/********************	WELCOME TOUR	********************/
function initTour() {
	//prevent body from moving
	document.body.classList.add('fix');

	//close welcome popup
	$('#welcomePopup').dialog('close');

	//create and append overlay that look the same as the error popup
	let overlay = document.createElement('div');
	overlay.classList.add('ui-widget-overlay', 'low');
	document.body.appendChild(overlay);
	setTimeout(() => overlay.classList.add('active'), 0); //fade it to a darker shade

	//vars
	let ID = 1; //current step
	let tipsSelector = `.tips[data-tips="${ID}"]`; //tips query selector
	let object = document.querySelector(tipsSelector); //current tip

	//function used to go to next tip
	function nextTip() {
		//make the tip visible + elevate the object that is referring to
		object.classList.add('visible');
		object.parentElement.classList.add('overlayed');

		//scroll to the tip position
		window.scrollTo({
			top: window.scrollY + object.getBoundingClientRect().top - 50
		});
	}

	//function to keep the scroll fixed when resizing the page
	function resize() {
		window.scrollTo({
			top: window.scrollY + object.getBoundingClientRect().top - 50
		});
	}

	//open first tip
	nextTip();

	//listener for click events to go to next tip
	window.addEventListener('click', function click(e) {
		//only handle tips elements
		if (e.target.getAttribute('data-action') != "next-tip" && e.target.getAttribute('data-action') != "close-tip")
			return;

		//prevent form submission etc.
		e.preventDefault();

		//update variables to next tip
		tipsSelector = tipsSelector.replace(ID, ID + 1);
		ID++;

		//hide current tip and update reference to next one
		object.classList.remove('visible');
		object.parentElement.classList.remove('overlayed');
		object = document.querySelector(tipsSelector);

		//this is the last tip
		if (e.target.getAttribute('data-action') == "close-tip") {
			//remove overlay
			overlay.remove();

			//unlock body
			document.body.classList.remove('fix');

			//scroll back to top
			window.scrollTo({
				top: 0,
				behavior: 'smooth'
			});

			//store first run in php
			setFirstRun();

			//remove listeners
			window.removeEventListener('resize', resize);
			window.removeEventListener('click', click);
		} else
			nextTip(); //go to next tip
	});

	//see resize()
	window.addEventListener('resize', resize);
}

function setFirstRun() {
	fetch('ajax.php?' + new URLSearchParams({
		module: phonemiddleware['ajax_name'],
		command: 'setfirstrun'
	}), []); //no check for errors
}

/********************	END WELCOME TOUR	********************/