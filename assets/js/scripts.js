/*
 * CardDAV Middleware UI
 * Written by Massi-X <firemetris@gmail.com> © 2023
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

//no check is done on the pm_language or other necessary variables. Please make sure they are present when including this file
document.addEventListener('DOMContentLoaded', () => {
	/********************	GET ELEMENTS	********************/
	carddav_url = document.getElementById('carddav_url');
	carddav_user = document.getElementById('carddav_user');
	carddav_psw = document.getElementById('carddav_psw');
	carddav_validate = document.getElementById('carddav_validate');
	carddav_display_url = document.getElementById('carddav_display_url');
	carddav_result_tbody = document.getElementById('carddav_result').getElementsByTagName('tbody')[0];

	/********************	START TAGIFY	********************/
	document.addEventListener('dragover', e => e.preventDefault());

	//tagify output_construct
	var output_construct = new Tagify(document.getElementById('output_construct'), {
		whitelist: [{
			"value": "fn",
			"name": pm_language['JS_fn'] + " (fn)"
		},
		{
			"value": "n",
			"name": pm_language['Name'] + " (n)"
		},
		{
			"value": "nickname",
			"name": pm_language['Nickname'] + " (nickname)"
		},
		{
			"value": "bday",
			"name": pm_language['Birthday'] + " (bday)"
		},
		{
			"value": "adr",
			"name": pm_language['Address'] + " (adr)"
		},
		{
			"value": "email",
			"name": pm_language['JS_email_adr'] + " (email)"
		},
		{
			"value": "title",
			"name": pm_language['Title'] + " (title)"
		},
		{
			"value": "role",
			"name": pm_language['Role'] + " (role)"
		},
		{
			"value": "org",
			"name": pm_language['JS_org'] + " (org)"
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
	let i = 1;
	while (true) {
		let name = pm_language['PHONE_TYPE_' + i];
		if (name === undefined)
			break;
		phone_type_whitelist.push({ 'value': i, 'name': name });
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
	//load data
	validateCarddav();

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

	//listener for sortable to set element width on dialog open
	$('#setupCarddav').on('dialogopen', (event, ui) => {
		sortableListResize();
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
	$('#setupCarddav').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	$('#errorPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	$('#licensePopupUI').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	$('#licensePopupCore').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	$('#librariesPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	window.magicPopup = $('#magicPopup').dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		draggable: false,
		height: 'auto',
		width: 'auto'
	});
	/********************	END INIT POPUPS	********************/

	/********************	LISTEN MAX_CNAM_OUTPUT CHECKBOX	********************/
	document.getElementById('max_cnam_length').onblur = e => {
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

	document.querySelectorAll('input[type="checkbox"][data-onchange]').forEach(elem => {
		let target = document.getElementById(elem.getAttribute('data-onchange'));

		elem.onchange = e => {
			e.target.checked ? target.removeAttribute('disabled') : target.setAttribute('disabled', 'disabled');
			target.value = target.getAttribute('min');
		}
	});
	/********************	END LISTEN MAX_CNAM_OUTPUT CHECKBOX	********************/

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

/********************	CARDDAV VALIDATION & SAVE	********************/
function validateCarddav() {
	let isSave = false;
	let checkboxes = document.querySelectorAll('input[type=checkbox][name="carddav_addressbooks[]"]'); //this changes at every request
	let request_type = 'ajax.php?module=' + phonemiddleware['ajax_name'] + '&command=';
	let formData = new FormData();
	let options = {
		method: 'post',
		body: formData
	};

	checkboxes.forEach(elem => { //determine if this is save
		if (elem.checked)
			isSave = true;
	});

	//construct base formdata request
	formData.append('carddav_url', carddav_url.value);
	formData.append('carddav_user', carddav_user.value);
	formData.append('carddav_psw', carddav_psw.value);

	if (isSave) {
		carddav_validate.innerHTML = '<i class="fa fa-spinner letsspin"></i> ' + pm_language['Saving_dots'];
		request_type += 'savecarddav';

		for (let i = 0; i < checkboxes.length; ++i) //add checked URLs to request
			if (checkboxes[i].checked)
				formData.append('carddav_addressbooks[]', checkboxes[i].getAttribute('data-uri'));
	} else {
		if (carddav_result_tbody.classList.contains('ui-sortable'))
			$(carddav_result_tbody).sortable("destroy"); //detach sortable before updating content
		carddav_validate.innerHTML = '<i class="fa fa-spinner letsspin"></i> ' + pm_language['Validating_dots'];
		carddav_result_tbody.innerHTML = '<tr><td colspan="4">' + pm_language['Loading_dots'] + '</td></tr>';
		request_type += 'validatecarddav';
	}

	//disable every input
	carddav_validate.setAttribute('disabled', 'disabled');
	carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = 'disabled';

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
				carddav_display_url.value = carddav_url.value;
				$('#setupCarddav').dialog('close');
			} else {
				carddav_result_tbody.innerHTML = ''; //reset content

				if (data.length == 0) //if nothing is found
					carddav_result_tbody.innerHTML = '<tr><td colspan="4"><b style="color: red">' + pm_language['No_addresbook_found'] + '</b></td></tr>';
				else {
					data.forEach(item => {
						carddav_result_tbody.innerHTML +=
							'<tr><td><i class="fa fa-bars ui-sortable-handle" title="' + pm_language['Move'] + '"></i></td><td><input type="checkbox" ' + (item['checked'] ? 'checked' : '') +
							' name="carddav_addressbooks[]" onclick="changeCarddavButton()" data-uri="' + item['uri'] +
							'"></td><td>' + item['name'] + '</td><td>' + item['uri'] + '</td></tr>';
					});
					sortableListResize();
					initSortableList();
				}
			}
			changeCarddavButton();
		}, error => {
			if (isSave) //only if there is an error during save alert the user
				alert(error.error.message);
			else //else we only show a generic no address book found inside the table
				carddav_result_tbody.innerHTML = '<tr><td colspan="4"><b style="color: red">' + error.error.message + '</b></td></tr>';
			changeCarddavButton();
		});
}

//this changed the button according to the current situation
function changeCarddavButton() {
	carddav_validate.innerHTML = pm_language['Validate'];
	carddav_validate.removeAttribute('disabled');
	carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = '';

	document.querySelectorAll('input[type=checkbox][name="carddav_addressbooks[]"]').forEach(elem => { //this change at every request
		if (elem.checked) {
			carddav_validate.innerHTML = pm_language['Save'];
			carddav_url.disabled = carddav_user.disabled = carddav_psw.disabled = 'disabled';
		}
	});
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
			ui.placeholder.html('<td colspan="' + cellCount + '">&nbsp;</td>');

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
	//no confirm
	let formData = new FormData();

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
