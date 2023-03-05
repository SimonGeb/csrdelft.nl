import axios from 'axios';

/**
 * Code voor de /instellingen pagina
 */
const instellingVeranderd = () => {
	document
		.querySelectorAll('.instellingen-bericht')
		.forEach((el) => el.classList.remove('d-none'));
};

export const instellingOpslaan = async (ev: Event) => {
	ev.preventDefault();

	const input = ev.target as HTMLElement;

	let href = null;
	let waarde = null;

	input.classList.add('loading');

	if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement) {
		if (!input.checkValidity()) {
			return false;
		}

		href = input.dataset.href;
		waarde = input.value;
	} else if (input instanceof HTMLAnchorElement) {
		href = input.href;
	}

	if (!href) {
		throw new Error('Geen url gevonden voor instelling');
	}

	await axios.post(href, { waarde });

	instellingVeranderd();

	input.classList.remove('loading');

};
