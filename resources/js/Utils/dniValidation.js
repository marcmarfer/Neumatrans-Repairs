export const dniFormats = {
  'ES': {
    text: 'España',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#c60b1e"/><rect width="640" height="240" y="240" fill="#ffc400"/></svg>',
    pattern: '[0-9]{8}[A-Z]',
    format: '12345678A',
    description: 'DNI: 8 números seguidos de una letra'
  },
  'PT': {
    text: 'Portugal',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#006600"/><rect width="640" height="240" y="240" fill="#ff0000"/></svg>',
    pattern: '[0-9]{9}',
    format: '123456789',
    description: 'NIF: 9 números'
  },
  'FR': {
    text: 'Francia',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="213.333" height="480" fill="#0055a4"/><rect x="213.333" width="213.334" height="480" fill="#ffffff"/><rect x="426.667" width="213.333" height="480" fill="#ef4135"/></svg>',
    pattern: '([0-9A-Z]{9}|[0-9A-Z]{12})',
    format: 'AB1234567',
    description: 'CNI: 9 o 12 caracteres alfanuméricos'
  },
  'IT': {
    text: 'Italia',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#009246"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#ce2b37"/></svg>',
    pattern: '[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]',
    format: 'RSSMRA70A01L726S',
    description: 'Codice Fiscale: 16 caracteres alfanuméricos'
  },
  'DE': {
    text: 'Alemania',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#000000"/><rect width="640" height="160" y="160" fill="#dd0000"/><rect width="640" height="160" y="320" fill="#ffce00"/></svg>',
    pattern: '[A-Z0-9]{9}',
    format: 'L01X00T47',
    description: 'Personalausweisnummer: 9 caracteres alfanuméricos'
  },
  'GB': {
    text: 'Reino Unido',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#012169"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#c8102e"/></svg>',
    pattern: '[A-Z]{2}[0-9]{6}[A-Z]',
    format: 'AB123456C',
    description: 'National Insurance Number: 2 letras, 6 números, 1 letra'
  },
  'MX': {
    text: 'México',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#006847"/><rect width="640" height="160" y="160" fill="#ffffff"/><rect width="640" height="160" y="320" fill="#ce1126"/></svg>',
    pattern: '^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$',
    format: 'ABCD123456HDFGJL09',
    description: 'CURP: 18 caracteres alfanuméricos'
  },
  'AR': {
    text: 'Argentina',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#74acdf"/><rect width="640" height="240" y="240" fill="#ffffff"/></svg>',
    pattern: '^[0-9]{7,8}$',
    format: '12345678',
    description: 'DNI: 7-8 dígitos'
  },
  'CO': {
    text: 'Colombia',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#FFD700"/><rect width="640" height="120" y="240" fill="#0033A0"/><rect width="640" height="120" y="360" fill="#CE1126"/></svg>',
    pattern: '[0-9]{10}',
    format: '1234567890',
    description: 'NIT: 10 números'
  },
  'CL': {
    text: 'Chile',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#ffffff"/><rect width="640" height="160" y="160" fill="#d52b1e"/><rect width="640" height="160" y="320" fill="#0039a6"/></svg>',
    pattern: '[0-9]{7,8}[0-9Kk]',
    format: '12345678K',
    description: 'RUT: 7-8 números + dígito verificador (0-9 o K)'
  },
  'PE': {
    text: 'Perú',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="240" y="0" fill="#ff0000"/><rect width="640" height="240" y="240" fill="#ffffff"/></svg>',
    pattern: '^[0-9]{8}$',
    format: '12345678',
    description: 'DNI: 8 dígitos'
  },
  'EC': {
    text: 'Ecuador',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#ffcd00"/><rect width="640" height="160" y="160" fill="#003893"/><rect width="640" height="160" y="320" fill="#d91023"/></svg>',
    pattern: '[0-9]{10}',
    format: '0912345678',
    description: 'Cédula de identidad: 10 dígitos'
  },
  'VE': {
    text: 'Venezuela',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><rect width="640" height="160" y="0" fill="#fcd116"/><rect width="640" height="160" y="160" fill="#003893"/><rect width="640" height="160" y="320" fill="#cf2027"/></svg>',
    pattern: '[0-9]{7,9}',
    format: '12345678',
    description: 'Cédula de identidad: 7 a 9 dígitos'
  },
  'BO': {
    text: 'Bolivia',
    icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480"><path fill="#da291c" d="M0 0h640v160H0z"/><path fill="#ffd700" d="M0 160h640v160H0z"/><path fill="#007a33" d="M0 320h640v160H0z"/></svg>',
    pattern: '^[0-9]{7,8}$',
    format: '12345678',
    description: 'Cédula de identidad: 7-8 dígitos'
  }
};

export const dniValidations = {
  'ES': (dni) => {
    const cleaned = dni.toUpperCase().trim();
    if (!/^[0-9]{8}[A-Z]$/.test(cleaned)) {
      return {
        ok: false,
        error: 'El DNI debe tener 8 números seguidos de una letra'
      };
    }

    const letters = "TRWAGMYFPDXBNJZSQVHLCKE";
    const number = parseInt(cleaned.slice(0, 8));
    const letter = cleaned.charAt(8);
    const calculatedLetter = letters.charAt(number % 23);

    if (letter !== calculatedLetter) {
      return {
        ok: false,
        error: 'La letra del DNI no es válida'
      };
    }

    return { ok: true };
  },
  'PT': (nif) => {
    const cleaned = nif.replace(/\s/g, '');
    if (!/^[0-9]{9}$/.test(cleaned)) {
      return { ok: false, error: 'El NIF debe tener 9 números' };
    }
    const digits = cleaned.split('').map(Number);
    const sum = digits.slice(0, 8).reduce((acc, d, i) => acc + d * (9 - i), 0);
    const remainder = sum % 11;
    const expected = remainder < 2 ? 0 : 11 - remainder;
    if (expected !== digits[8]) {
      return { ok: false, error: 'El dígito de control del NIF portugués no es válido' };
    }
    return { ok: true };
  },
  'FR': (nir) => {
    const cleaned = nir.toUpperCase().trim();
    if (!/^([0-9A-Z]{9}|[0-9A-Z]{12})$/.test(cleaned)) {
      return { ok: false, error: 'El CNI debe tener 9 o 12 caracteres alfanuméricos' };
    }
    return { ok: true };
  },
  'IT': (cf) => {
    const cleaned = cf.toUpperCase().trim();
    if (!/^[A-Z]{6}[0-9]{2}[A-Z][0-9]{2}[A-Z][0-9]{3}[A-Z]$/.test(cleaned)) {
      return { ok: false, error: 'El Codice Fiscale debe tener el formato correcto (16 caracteres)' };
    }
    const oddMap = { '0':1,'1':0,'2':5,'3':7,'4':9,'5':13,'6':15,'7':17,'8':19,'9':21,
                      'A':1,'B':0,'C':5,'D':7,'E':9,'F':13,'G':15,'H':17,'I':19,'J':21,
                      'K':2,'L':4,'M':18,'N':20,'O':11,'P':3,'Q':6,'R':8,'S':12,
                      'T':14,'U':16,'V':10,'W':22,'X':25,'Y':24,'Z':23 };
    const evenMap = { '0':0,'1':1,'2':2,'3':3,'4':4,'5':5,'6':6,'7':7,'8':8,'9':9,
                      'A':0,'B':1,'C':2,'D':3,'E':4,'F':5,'G':6,'H':7,'I':8,'J':9,
                      'K':10,'L':11,'M':12,'N':13,'O':14,'P':15,'Q':16,'R':17,'S':18,
                      'T':19,'U':20,'V':21,'W':22,'X':23,'Y':24,'Z':25 };
    let sum = 0;
    for (let i = 0; i < 15; i++) {
      const c = cleaned.charAt(i);
      sum += (i % 2 === 0) ? oddMap[c] : evenMap[c];
    }
    const expectedLetter = String.fromCharCode('A'.charCodeAt(0) + (sum % 26));
    if (cleaned.charAt(15) !== expectedLetter) {
      return { ok: false, error: 'El carácter de control del Codice Fiscale no es válido' };
    }
    return { ok: true };
  },
  'DE': (dni) => {
    const cleaned = dni.replace(/\s/g, '').toUpperCase();
    if (!/^[A-Z0-9]{9}$/.test(cleaned)) {
      return {
        ok: false,
        error: 'La Personalausweisnummer debe tener 9 caracteres alfanuméricos'
      };
    }
    return { ok: true };
  },
  'GB': (nin) => {
    const cleaned = nin.toUpperCase().trim();
    if (!/^[A-Z]{2}[0-9]{6}[A-Z]$/.test(cleaned)) {
      return {
        ok: false,
        error: 'El NIN debe tener el formato correcto (2 letras, 6 números, 1 letra)'
      };
    }
    return { ok: true };
  },
  'MX': (curp) => {
    const cleaned = curp.toUpperCase().trim();
    if (!/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9]{2}$/.test(cleaned)) {
      return { ok: false, error: 'El CURP debe tener 18 caracteres y formato válido' };
    }
    return { ok: true };
  },
  'AR': (dni) => {
    const cleaned = dni.replace(/\s/g, '');
    if (!/^[0-9]{7,8}$/.test(cleaned)) {
      return { ok: false, error: 'El DNI debe tener 7-8 dígitos' };
    }
    return { ok: true };
  },
  'CO': (nit) => {
    const cleaned = nit.replace(/\s/g, '');
    if (!/^[0-9]{10}$/.test(cleaned)) {
      return { ok: false, error: 'El NIT debe tener 10 números' };
    }
    return { ok: true };
  },
  'CL': (rut) => {
    const cleaned = rut.replace(/[\.\-]/g, '').toUpperCase();
    if (!/^[0-9]{7,8}[0-9K]$/.test(cleaned)) {
      return { ok: false, error: 'El RUT debe tener 7-8 dígitos más dígito verificador (0-9 o K)' };
    }
    const body = cleaned.slice(0, -1);
    const dv = cleaned.slice(-1);
    let sum = 0; let factor = 2;
    for (let i = body.length - 1; i >= 0; i--) {
      sum += parseInt(body.charAt(i), 10) * factor;
      factor = factor === 7 ? 2 : factor + 1;
    }
    const dvCalcNum = 11 - (sum % 11);
    const dvCalc = dvCalcNum === 11 ? '0' : dvCalcNum === 10 ? 'K' : String(dvCalcNum);
    if (dvCalc !== dv) {
      return { ok: false, error: 'El dígito verificador del RUT no es válido' };
    }
    return { ok: true };
  },
  'PE': (dni) => {
    const cleaned = dni.replace(/\s/g, '');
    if (!/^[0-9]{8}$/.test(cleaned)) {
      return { ok: false, error: 'El DNI debe tener 8 dígitos' };
    }
    return { ok: true };
  },
  'EC': (cedula) => {
    const cleaned = cedula.replace(/\s/g, '');
    if (!/^[0-9]{10}$/.test(cleaned)) {
      return { ok: false, error: 'La cédula de identidad debe tener 10 dígitos' };
    }
    return { ok: true };
  },
  'VE': (cedula) => {
    const cleaned = cedula.replace(/\s/g, '');
    if (!/^[0-9]{7,9}$/.test(cleaned)) {
      return { ok: false, error: 'La cédula de identidad debe tener entre 7 y 9 dígitos' };
    }
    return { ok: true };
  },
  'BO': (cedula) => {
    const cleaned = cedula.replace(/\s/g, '');
    if (!/^[0-9]{7,8}$/.test(cleaned)) {
      return { ok: false, error: 'La cédula de identidad debe tener 7-8 dígitos' };
    }
    return { ok: true };
  }
}; 