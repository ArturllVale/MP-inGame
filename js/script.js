const cardBox = document.querySelector('div.card-content-box')
const btnRotateCard = document.querySelector('#rotate-card')
const btnSubmit = document.querySelector('#input-submit')


const inputNumber = document.querySelector('#cardNumber')
const inputNumberInfo = document.querySelector('#cardNumber + .info')
const inputName = document.querySelector('#cardholderName')
const inputNameInfo = document.querySelector('#cardholderName + .info')
const inputCvv = document.querySelector('#securityCode')
const inputCvvInfo = document.querySelector('#securityCode + .info')
const inputValidate = document.querySelector('#cardExpirationYear')
const inputValidateInfo = document.querySelector('#cardExpirationYear + .info')

const cardViewName = document.querySelector('#card-user-name');
const cardViewNumber = document.querySelector('#card-user-number');
const cardViewCvv = document.querySelector('#card-user-cvv');
const cardViewDate = document.querySelector('#card-user-date');


inputNumber.onblur = (e) => {
	const value = e.target.value;
	const valueReplace = value.replaceAll(' ', '')


	if (value.length <= 0) {
		const message = "Preenchimento obrigatório!"
		inputNumberInfo.querySelector('.message').innerText = message
		inputNumberInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	if (!/^[0-9]{16}$/.test(valueReplace)) {
		const message = "Use apenas números, e verifique se estão completos!"
		inputNumberInfo.querySelector('.message').innerText = message
		inputNumberInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	inputNumberInfo.querySelector('.message').innerText = ''
	inputNumberInfo.classList.remove('visible')

	canSubmit();
}

inputName.onblur = (e) => {
	const value = e.target.value;
	const valueReplace = value.replaceAll(' ', '')


	if (value.length <= 0) {
		const message = "Preenchimento obrigatório!"
		inputNameInfo.querySelector('.message').innerText = message
		inputNameInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	if (!/^[a-z]+$/i.test(valueReplace)) {
		const message = "Insira seu nome de forma correcta!"
		inputNameInfo.querySelector('.message').innerText = message
		inputNameInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	inputNameInfo.querySelector('.message').innerText = ''
	inputNameInfo.classList.remove('visible')
	canSubmit();
}

inputValidate.onblur = (e) => {
	const value = e.target.value;
	const valueReplace = value.replaceAll(' ', '')


	if (value.length <= 0) {
		const message = "Preenchimento obrigatório!"
		inputValidateInfo.querySelector('.message').innerText = message
		inputValidateInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	if (!/^[0-9]{2}\/[0-9]{4}/i.test(valueReplace)) {
		const message = `Use o padrão "mês/ano"`
		inputValidateInfo.querySelector('.message').innerText = message
		inputValidateInfo.classList.add('visible')
		btnSubmit.classList.add('disable')
		return false;
	}

	inputValidateInfo.querySelector('.message').innerText = ''
	inputValidateInfo.classList.remove('visible')
	canSubmit();
}

btnRotateCard.addEventListener('click', (e) => {
	cardBox.classList.toggle('rotate')
})

const handleName = (e) => {

	setTimeout(() => {

		const value = e.target.value

		cardViewName.innerText = value

	}, 100)

}

const handleNumber = (e) => {

	setTimeout(() => {

		const value = e.target.value


		if (value.length >= 20) {
			return false;
		}

		if (e.key == 'Backspace') {
			cardViewNumber.innerText = value
			return false
		}

		if (value.length == 4 || value.length == 9 || value.length == 14) {

			e.target.value += " "
		}

		cardViewNumber.innerText = value

	}, 0)

}

const handleCvv = (e) => {

	setTimeout(() => {

		const value = e.target.value


		cardViewCvv.innerText = value

	}, 0)

}

const handleValidate = (e) => {

	setTimeout(() => {

		const value = e.target.value


		cardViewDate.innerText = value

	}, 0)

}

inputCvv.onfocus = () => {
	cardBox.classList.remove('rotate')
}

inputCvv.onblur = () => {
	cardBox.classList.add('rotate')
	canSubmit();
}

function canSubmit() {

	const inputs = document.querySelectorAll('input')

	for (let i = 0; i < 5; i++) {

		if (inputs[i].value.length <= 0) {
			btnSubmit.classList.add('disable')
			return false;
		}
	}

	btnSubmit.classList.remove('disable')
}

canSubmit()

function identifyCreditCard(creditCardNumber) {
	const cardPatterns = {
		'visa': /^4\d{12}(\d{3})?$/,
		'mastercard': /^5[1-5]\d{14}$/,
		'amex': /^3[47]\d{13}$/,
		'mastercard': /^(5[0-5]02[0-9]{8}|516292[0-9]{8}|522688[0-9]{8}|523421[0-9]{8})$/,
		'hipercard': /^(606282\d{10}(\d{3})?)|(3841\d{15})$/,
		'elo': /^4011(78|79)|^43(1274|8935)|^45(1416|7393|763(1|2))|^504175|^627780|^63(6297|6368|6369)|(65003[5-9]|65004[0-9]|65005[01])|(65040[5-9]|6504[1-3][0-9])|(65048[5-9]|65049[0-9]|6505[0-2][0-9]|65053[0-8])|(65054[1-9]|6505[5-8][0-9]|65059[0-8])|(65070[0-9]|65071[0-8])|(65072[0-7])|(65090[1-9]|6509[1-6][0-9]|65097[0-8])|(65165[2-9]|6516[67][0-9])|(65500[0-9]|65501[0-9])|(65502[1-9]|6550[34][0-9]|65505[0-8])|^(506699|5067[0-6][0-9]|50677[0-8])|^(509[0-8][0-9]{2}|5099[0-8][0-9]|50999[0-9])|^65003[1-3]|^(65003[5-9]|65004\d|65005[0-1])|^(65040[5-9]|6504[1-3]\d)|^(65048[5-9]|65049\d|6505[0-2]\d|65053[0-8])|^(65054[1-9]|6505[5-8]\d|65059[0-8])|^(65070\d|65071[0-8])|^65072[0-7]|^(65090[1-9]|65091\d|650920)|^(65165[2-9]|6516[6-7]\d)|^(65500\d|65501\d)|^(65502[1-9]|6550[3-4]\d|65505[0-8])/
	};

	for (let card in cardPatterns) {
		if (cardPatterns[card].test(creditCardNumber)) {
			return card;
		}
	}

	return '';
}

function updateCreditCardInfo() {
	let cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
	let bandeira = identifyCreditCard(cardNumber);
	let imagemBandeira = document.getElementById('imagemBandeira');

	if (bandeira !== '') {
		imagemBandeira.style.display = 'inline'; // 
		switch (bandeira) {
			case 'visa':
				imagemBandeira.src = '../assets/visa.png';
				imagemBandeira.alt = 'Visa';
				break;
			case 'mastercard':
				imagemBandeira.src = '../assets/mastercard.png';
				imagemBandeira.alt = 'Mastercard';
				break;
			case 'amex':
				imagemBandeira.src = '../assets/amexx.png';
				imagemBandeira.alt = 'Amex';
				break;
			case 'elo':
				imagemBandeira.src = '../assets/elo.png';
				imagemBandeira.alt = 'Elo';
				break;
			case 'hipercard':
				imagemBandeira.src = '../assets/hipercard.png';
				imagemBandeira.alt = 'hipercard';
				break;
			default:

				imagemBandeira.src = '';
				imagemBandeira.alt = '';
		}
	} else {
		imagemBandeira.style.display = 'none';
		imagemBandeira.style.width = '50px';
	}
}