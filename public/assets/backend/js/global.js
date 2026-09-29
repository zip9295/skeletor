import Config from "../../../vendor/skeletorjs/src/Config/Config.js";
import Translator from "../../../vendor/skeletorjs/src/Translator/Translator.js";
import {translations} from "./config/translations.js";

// Dynamic import because config-local.js is per-install and gitignored: a checkout that
// has not created one yet should log and carry on rather than fail the whole module.
const configDirectory = './config';
import(`${configDirectory}/config-local.js`).then(({configLocal: configLocal}) => {
    Object.keys(configLocal).forEach((key) => {
        Config.set(key, configLocal[key]);
    });
}).catch((e) => {
    console.error(e);
    console.error('No config local found.');
});

Translator.setTranslations(translations);
Translator.setLanguage('en');
