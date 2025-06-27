export const telfPrefixes = {
    '+34': {
        text: '(+34) España',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#c60b1e"/><rect width="640" height="240" y="240" fill="#ffc400"/></svg>',
        format: '612 345 678'
    },
    '+351': {
        text: '(+351) Portugal',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#006600"/><rect width="640" height="240" y="240" fill="#ff0000"/></svg>',
        format: '912 345 678'
    },
    '+33': {
        text: '(+33) Francia',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="213.333" height="480" fill="#0055a4"/><rect x="213.333" width="213.334" height="480" fill="#ffffff"/><rect x="426.667" width="213.333" height="480" fill="#ef4135"/></svg>',
        format: '06 12 34 56 78'
    },
    '+39': {
        text: '(+39) Italia',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#009246"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#ce2b37"/></svg>',
        format: '312 345 6789'
    },
    '+49': {
        text: '(+49) Alemania',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#000000"/><rect width="640" height="160" y="160" fill="#dd0000"/><rect width="640" height="160" y="320" fill="#ffce00"/></svg>',
        format: '0151 23456789'
    },
    '+44': {
        text: '(+44) Reino Unido',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#012169"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#c8102e"/></svg>',
        format: '07123 456789'
    },
    '+52': {
        text: '(+52) México',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#006847"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#ce1126"/></svg>',
        format: '55 1234 5678'
    },
    '+54': {
        text: '(+54) Argentina',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#74acdf"/><rect width="640" height="240" y="240" fill="#ffffff"/></svg>',
        format: '11 2345-6789'
    },
    '+57': {
        text: '(+57) Colombia',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#FFD700"/><rect width="640" height="120" y="240" fill="#0033A0"/><rect width="640" height="120" y="360" fill="#CE1126"/></svg>',
        format: '321 1234567'
    },
    '+56': {
        text: '(+56) Chile',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#ffffff"/><rect width="640" height="160" y="160" fill="#d52b1e"/><rect width="640" height="160" y="320" fill="#0039a6"/></svg>',
        format: '9 1234 5678'
    },
    '+51': {
        text: '(+51) Perú',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#ff0000"/><rect width="640" height="240" y="240" fill="#ffffff"/></svg>',
        format: '912 345 678'
    },
    '+593': {
        text: '(+593) Ecuador',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#ffcd00"/><rect width="640" height="160" y="160" fill="#003893"/><rect width="640" height="160" y="320" fill="#d91023"/></svg>',
        format: '099 123 4567'
    },
    '+58': {
        text: '(+58) Venezuela',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#fcd116"/><rect width="640" height="160" y="160" fill="#003893"/><rect width="640" height="160" y="320" fill="#cf2027"/></svg>',
        format: '0412 1234567'
    },
    '+591': {
        text: '(+591) Bolivia',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#da291c"/><rect width="640" height="160" y="160" fill="#ffd700"/><rect width="640" height="160" y="320" fill="#007a33"/></svg>',
        format: '71234567'
    }
};

export const prefixesValidations = {
    '+34': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[6-9][0-9]{8}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 6-9 y tener 9 dígitos'
            };
        }
        return { ok: true };
    },
    '+351': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^9[1236][0-9]{7}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 91, 92, 93 o 96 y tener 9 dígitos'
            };
        }
        return { ok: true };
    },
    '+33': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[67][0-9]{8}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 6 o 7 y tener 9 dígitos'
            };
        }
        return { ok: true };
    },
    '+39': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^3[0-9]{9}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 3 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+49': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^1[567][0-9]{8}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 15, 16 o 17 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+44': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^7[1-9][0-9]{8}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 7 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+1': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[2-9][0-9]{2}[2-9][0-9]{6}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe tener 10 dígitos en formato NXX-NXX-XXXX'
            };
        }
        return { ok: true };
    },
    '+52': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[1-9][0-9]{9}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 1-9 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+54': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^9[0-9]{8,10}$/.test(cleaned) || cleaned.length < 9 || cleaned.length > 11) {
            return {
                ok: false,
                error: 'El número debe empezar por 9 y tener entre 9-11 dígitos'
            };
        }
        return { ok: true };
    },
    '+57': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^3[0-9]{9}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 3 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+56': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[89][0-9]{7,8}$/.test(cleaned) || (cleaned.length !== 8 && cleaned.length !== 9)) {
            return {
                ok: false,
                error: 'El número debe empezar por 8 o 9 y tener 8-9 dígitos'
            };
        }
        return { ok: true };
    },
    '+51': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^9[0-9]{8}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 9 y tener 9 dígitos'
            };
        }
        return { ok: true };
    },
    '+593': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[89][0-9]{7}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 8 o 9 y tener 8 dígitos'
            };
        }
        return { ok: true };
    },
    '+58': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^4[0-9]{9}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 4 y tener 10 dígitos'
            };
        }
        return { ok: true };
    },
    '+591': (phone) => {
        const cleaned = phone.replace(/\s/g, '');
        if (!/^[67][0-9]{7}$/.test(cleaned)) {
            return {
                ok: false,
                error: 'El número debe empezar por 6 o 7 y tener 8 dígitos'
            };
        }
        return { ok: true };
    }
}; 