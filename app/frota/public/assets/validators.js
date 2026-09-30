const V = {
  digits: s => String(s).replace(/\D/g, ''),
  cpf(s) {
    const c = V.digits(s); if (c.length !== 11 || /^(\d)\1{10}$/.test(c)) return false;
    for (let t = 9; t < 11; t++) { let sum = 0; for (let i = 0; i < t; i++) sum += +c[i] * (t + 1 - i); if (+c[t] !== ((10 * sum) % 11) % 10) return false; }
    return true;
  },
  cnpj(s) {
    const c = V.digits(s); if (c.length !== 14 || /^(\d)\1{13}$/.test(c)) return false;
    for (const t of [12, 13]) {
      const w = t === 12 ? [5,4,3,2,9,8,7,6,5,4,3,2] : [6,5,4,3,2,9,8,7,6,5,4,3,2];
      const sum = w.reduce((a, p, i) => a + +c[i] * p, 0), d = sum % 11 < 2 ? 0 : 11 - sum % 11;
      if (+c[t] !== d) return false;
    } return true;
  },
  phone: s => /^[1-9][1-9](9\d{8}|[2-5]\d{7})$/.test(V.digits(s)),
  date(s) { const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s); if (!m) return false; const d = new Date(+m[1], m[2] - 1, +m[3]); return d.getFullYear() == m[1] && d.getMonth() == m[2] - 1 && d.getDate() == m[3]; },
  placa: s => /^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/.test(String(s).toUpperCase().replace(/[^A-Z0-9]/g, '')),
  email: s => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(s),
};
const MASK = {
  cpf: s => V.digits(s).slice(0, 11).replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2'),
  cnpj: s => V.digits(s).slice(0, 14).replace(/(\d{2})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1/$2').replace(/(\d{4})(\d{1,2})$/, '$1-$2'),
  phone: s => { const d = V.digits(s).slice(0, 11); return d.length > 10 ? d.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3') : d.replace(/(\d{2})(\d{4})(\d{0,4})/, '($1) $2-$3').replace(/-$/, ''); },
  placa: s => String(s).toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 7),
};
// retorna mensagem de erro ou ''
function validateField(type, value, req) {
  const v = String(value ?? '').trim();
  if (!v) return req ? 'Campo obrigatório' : '';
  const bad = { cpf: 'CPF inválido', cnpj: 'CNPJ inválido', phone: 'Telefone inválido', date: 'Data inválida', placa: 'Placa inválida', email: 'E-mail inválido' };
  return V[type] && !V[type](v) ? bad[type] : '';
}
