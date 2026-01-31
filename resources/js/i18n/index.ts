import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import tr from './locales/tr.json';

export type MessageSchema = typeof tr;

export const i18n = createI18n<[MessageSchema], 'tr' | 'en'>({
    legacy: false,
    locale: 'tr',
    fallbackLocale: 'en',
    messages: {
        tr,
        en,
    },
});
