/*
 * CardDAV Middleware UI
 * Written by Massi-X <support@massi-x.dev> © 2024
 * This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
 */

//declaration: new TimePicker(target element, -optional- minutesStep)
class TimePicker {
	#hiddenInput;
	#daysInput;
	#hoursInput;
	#minutesInput;
	#minutesStep;
	#previousMinutes;

	constructor(elem, minutesStep) {
		if (!(/^input$/i).test(elem.nodeName)) //only allow inputs
			return;

		if (minutesStep == undefined)
			minutesStep = 1;

		this.#minutesStep = minutesStep;
		this.#hiddenInput = elem;

		let parts = this._calculatePiecesFromValue(this.#hiddenInput.value);

		parts[2] = Math.round(parts[2] / minutesStep) * minutesStep; //normalize to step
		this.#previousMinutes = parts[2];

		//create new element
		let timepicker = document.createElement('div');
		timepicker.className = '__timepicker-container';
		if (elem.classList != null) //add existing classes if != null
			timepicker.classList.add(elem.classList);

		timepicker.insertAdjacentHTML('beforeend',
			'<div class="__timepicker-div">' +
			'<div class="__timepicker-button-container" >' +
			'<label data-timepicker-function="increment days" tabindex="0"><div class="__timepicker-arrow-up"></div></label>' +
			'<label data-timepicker-function="decrement days" tabindex="0"><div class="__timepicker-arrow-down"></div></label>' +
			'</div>' +
			'<input type="number" data-timepicker-days min="0" value="' + parts[0] + '" disabled>' +
			'<span>Days</span>' +
			'</div>');
		timepicker.insertAdjacentHTML('beforeend',
			'<div class="__timepicker-div">' +
			'<div class="__timepicker-button-container" data-target="data-timepicker-hours">' +
			'<label data-timepicker-function="increment hours" tabindex="0"><div class="__timepicker-arrow-up"></div></label>' +
			'<label data-timepicker-function="decrement hours" tabindex="0"><div class="__timepicker-arrow-down"></div></label>' +
			'</div>' +
			'<input type="number" data-timepicker-hours min="-1" value="' + parts[1] + '" disabled>' +
			'<span>Hours</span>' +
			'</div>');
		timepicker.insertAdjacentHTML('beforeend',
			'<div class="__timepicker-div">' +
			'<div class="__timepicker-button-container" data-target="data-timepicker-minutes">' +
			'<label data-timepicker-function="increment minutes" tabindex="0"><div class="__timepicker-arrow-up"></div></label>' +
			'<label data-timepicker-function="decrement minutes" tabindex="0"><div class="__timepicker-arrow-down"></div></label>' +
			'</div>' +
			'<input type="number" data-timepicker-minutes min="-1" value="' + parts[2] + '" disabled>' +
			'<span>Minutes</span>' +
			'</div>');

		elem.parentNode.insertBefore(timepicker, elem.nextSibling); //indert into DOM
		elem.type = 'hidden'; //hide original input

		//save inputs
		this.#daysInput = timepicker.querySelectorAll('[data-timepicker-days]')[0];
		this.#hoursInput = timepicker.querySelectorAll('[data-timepicker-hours]')[0];
		this.#minutesInput = timepicker.querySelectorAll('[data-timepicker-minutes]')[0];

		//listeners
		timepicker.querySelectorAll('[data-timepicker-function]').forEach((elem) => {
			var func = elem.getAttribute('data-timepicker-function').split(" ");
			var self = this;

			elem.intervalTime = 600;
			elem.onmouseup = elem.onmouseleave = elem.onkeyup = () => {
				clearTimeout(elem.timeoutId)
				elem.intervalTime = 600;
			};
			elem.onkeydown = e => {
				if (e.key === 'Enter')
					elem.dispatchEvent(new Event('mousedown'));
			};

			var repeat = (type, decrease = false) => {
				if (elem.intervalTime > 100)
					elem.intervalTime /= 3.5;

				if (type == "days")
					decrease ? self.#daysInput.value-- : self.#daysInput.value++;
				else if (type == "hours")
					decrease ? self.#hoursInput.value-- : self.#hoursInput.value++;
				else if (type == "minutes")
					decrease ? self.#minutesInput.value-- : self.#minutesInput.value++;

				timepicker.dispatchEvent(new Event('input'));
			};

			if ("increment" == func[0])
				elem.onmousedown = () => {
					elem.timeoutId = setTimeout(() => { elem.dispatchEvent(new Event('mousedown')) }, elem.intervalTime);
					repeat(func[1]);
				};
			else if ("decrement" == func[0])
				elem.onmousedown = () => {
					elem.timeoutId = setTimeout(() => { elem.dispatchEvent(new Event('mousedown')) }, elem.intervalTime);
					repeat(func[1], true);
				};
		});
		timepicker.oninput = () => {
			//calculate step
			if (this.#minutesInput.value != -1) {
				if (this.#minutesInput.value - this.#previousMinutes == 1) //increment
					this.#minutesInput.value = +this.#minutesInput.value + this.#minutesStep - 1;
				else if (this.#minutesInput.value - this.#previousMinutes == -1) //decrement
					this.#minutesInput.value = +this.#minutesInput.value - this.#minutesStep + 1;
			}

			//thanks to https://codepen.io/denilsonsa/pen/ZGYEEpD
			if (this.#minutesInput.value == -1) {
				if (this.#hoursInput.value > 0 || this.#daysInput.value > 0) {
					this.#hoursInput.value--;
					this.#minutesInput.value = 60 - this.#minutesStep;
				} else {
					this.#minutesInput.value = 0;
				}
			} else if (this.#minutesInput.value == 60) {
				this.#hoursInput.value++;
				this.#minutesInput.value = 0;
			}

			if (this.#hoursInput.value == -1) {
				if (this.#daysInput.value > 0) {
					this.#daysInput.value--;
					this.#hoursInput.value = 23;
				} else {
					this.#hoursInput.value = 0;
				}
			} else if (this.#hoursInput.value == 24) {
				this.#daysInput.value++;
				this.#hoursInput.value = 0;
			}

			if (this.#daysInput.value == -1) {
				this.#daysInput.value = 0;
			}

			this._updateHiddenInput();

			//check if it's over the max
			if (+this.#hiddenInput.value > +this.#hiddenInput.max) {
				let parts = this._calculatePiecesFromValue(this.#hiddenInput.max);

				this.#daysInput.value = parts[0];
				this.#hoursInput.value = parts[1];
				this.#minutesInput.value = parts[2];

				this._updateHiddenInput();
			}

			this.#previousMinutes = this.#minutesInput.value;
		};
	}

	_updateHiddenInput() {
		this.#hiddenInput.value = this.#daysInput.value * 24 * 60 + this.#hoursInput.value * 60 + this.#minutesInput.value * 1; //by 1 to convert to integer
		if (this.#hiddenInput.value < 0)
			this.#hiddenInput.value = 0;
	}

	_calculatePiecesFromValue(val) {
		let days = val / (24 * 60);
		let daysTruncated = Math.trunc(days);
		let hours = (days - daysTruncated) * 24;
		let hoursTruncated = Math.trunc(hours);
		let minutesTruncated = Math.round((hours - hoursTruncated) * 60); //needs rounding for precision errors

		return [daysTruncated, hoursTruncated, minutesTruncated];
	}
}