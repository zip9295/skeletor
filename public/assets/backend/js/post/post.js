import ContentEditor from "../../../../vendor/skeletorjs/src/ContentEditor/ContentEditor.js";
import MediaLibrary from "../../../../vendor/skeletorjs/src/MediaLibrary/MediaLibrary.js";
import Excerpt from "./Modules/Excerpt.js";
import {config} from "../config/config.js";
import SaveResponse from "../../../../vendor/skeletorjs/src/ContentEditor/SaveResponse.js";
import {events} from "../../../../vendor/skeletorjs/src/ContentEditor/events.js";
import {events as tagEvents} from "../../../../vendor/skeletorjs/src/ContentEditor/Tag/events.js";
import Message from "../../../../vendor/skeletorjs/src/Message/Message.js";
import Translator from "../../../../vendor/skeletorjs/src/Translator/Translator.js";
import FailedValidation from "../../../../vendor/skeletorjs/src/ContentEditor/SaveValidation/FailedValidation.js";
import Back from "../../../../vendor/skeletorjs/src/ContentEditor/Back/Back.js";

window.mediaLibrary = new MediaLibrary();
window.mediaLibrary.init();

ContentEditor.registerModule('excerpt', {
    class: Excerpt
});

let slug = initialContent.slug ?? '';

const contentEditor = new ContentEditor({
    config: config.postContentEditor,
    initialContent: initialContent ?? null
});

Back.registerBackUrl('/post/view');

contentEditor.saveValidationHandler.registerValidation({
    name: 'categoryValidation',
    callback: () => {
        const categories = contentEditor.getModule('categories');
        if (categories && categories.getSelectedCategoryIds().length === 0) {
            return new FailedValidation(['Category is required.']);
        }
        return true;
    }
});
contentEditor.saveValidationHandler.registerValidation({
    name: 'titleValidation',
    callback: () => {
        const title = contentEditor.getModule('title');
        if (title && title.getValue().trim() === '') {
            return new FailedValidation(['Title is required.']);
        }
        return true;
    }
});

contentEditor.saveValidationHandler.registerValidation({
    name: 'featuredImageValidation',
    callback: () => {
        const featuredImage = contentEditor.getModule('featuredImage').getData();
        if (!featuredImage.id || !featuredImage.src) {
            return new FailedValidation(['Featured image is required.']);
        }
        return true;
    }
});
contentEditor.saveValidationHandler.registerValidation({
    name: 'authorValidation',
    callback: () => {
        const authors = contentEditor.getModule('authors');
        if (authors && authors.getSelectedAuthorIds().length === 0) {
            return new FailedValidation(['Author is required.']);
        }
        return true;
    }
});

contentEditor.saveValidationHandler.registerValidation({
    name: 'seoValidation',
    callback: () => {
        const seo = contentEditor.getModule('seo').getData();
        const errors = [];
        if(seo.title.trim() === '') {
            errors.push(['SEO title is required.']);
        }
        if(seo.description.trim() === '') {
            errors.push(['SEO description is required.']);
        }
        if(!seo.image.id || !seo.image.src) {
            errors.push(['SEO image is required.']);
        }
        if(errors.length > 0) {
            return new FailedValidation(errors);
        }
        return true;
    }
});

contentEditor.getModule('authors')?.disableMultipleAuthors();


contentEditor.eventEmitter.on(tagEvents.beforeNewTagCallback, (formData) => {
    const csrf = document.querySelector('input[name^="_csrf"]');
    formData.append(csrf.name, csrf.value);
    formData.append('slug', '');
});

contentEditor.eventEmitter.on(tagEvents.afterNewTagCallback, (resData) => {
    if(!resData.status) {
        Message.spawn({
            message: Translator.translate('An error occurred while creating the tag.'),
            type: Message.TYPES.ERROR,
            view: {
                type: Message.VIEW_TYPES.STATIC,
                container: contentEditor.messagesContainer,
                prepend: false,
            }
        });
    } else {
        if(resData.data?.id && resData.data?.title)
        contentEditor.getModule('tags')?.addTag(resData.data.id, resData.data.title);
    }
    if(resData.token) {
        const csrf = document.querySelector('input[name^="_csrf"]');
        if(csrf) {
            csrf.remove();
        }
        document.body.insertAdjacentHTML('afterbegin', resData.token);
    }
});

contentEditor.eventEmitter.on(events.beforeSave, (data) => {
    const csrf = document.querySelector('input[name^="_csrf"]');
    data[csrf.name] = csrf.value;
});

contentEditor.save = async (data) => {
    const formData = new FormData();

    Object.entries(data).forEach(([key, value]) => {
        if (typeof value === 'object' && value !== null) {
            formData.append(key, JSON.stringify(value));
        } else {
            formData.append(key, value);
        }
    });
    const res = await fetch(action, {
        method: 'POST',
        body: formData,
    });
    const resData = await res.json();
    const messages = [];
    if(resData.errors.length > 0) {
        resData.errors.forEach((error) => {
            messages.push(error.message);
        });
    }
    if(resData.generalErrors.length > 0) {
        resData.generalErrors.forEach((error) => {
            messages.push(error.message);
        });
    }
    if(resData.status) {
        messages.push(resData.message);
        if(resData.data.id) {
            action = `/post/update/${resData.data.id}/`;
        }
        if(resData.data.slug) {
            contentEditor.getModule('slug')?.setValue(resData.data.slug);
            slug = resData.data.slug;
        }
    }
    if(resData.token) {
        const csrf = document.querySelector('input[name^="_csrf"]');
        if(csrf) {
            csrf.remove();
        }
        document.body.insertAdjacentHTML('afterbegin', resData.token);
    }
    return new SaveResponse({success: resData.status, messages});
};

await contentEditor.init();