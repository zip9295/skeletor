import Loader from "/vendor/skeletorjs/src/Loader/Loader.js";
import Message from "/vendor/skeletorjs/src/Message/Message.js";
export default class ThemeColor {
    #form;
    #submitEndpoint = '/themecolor/save/'
    #submitLoader = new Loader({size: '22px', thickness: '3px'});

    init() {
        this.#form = document.getElementById('themeColor');
        this.#form.addEventListener('submit', this.#submitForm);
    }

    #submitForm = async (e) => {
        e.preventDefault();
        this.#submitLoader.start(document.querySelector('.submitContainer'), ['input']);
        const formData = new FormData(this.#form);
        const response = await fetch(this.#submitEndpoint, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        Message.spawn({
            message: data.message,
            type: data.status ? Message.TYPES.SUCCESS : Message.TYPES.ERROR,
            view: {
                type: Message.VIEW_TYPES.NOTIFICATION,
                container: document.getElementById('messageContainerFixed'),
            },
            ephemeralTimeout: 5000
        });
        this.#submitLoader.stop(document.querySelector('.submitContainer'), ['input']);
    }

    destroy() {
        this.#form.removeEventListener('submit', this.#submitForm);
        this.#submitForm = null;
    }
}