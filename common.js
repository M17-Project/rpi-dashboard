// Escape text for insertion into HTML. Everything that comes from the gateway
// log (call signs, text messages) is untrusted: it arrives over RF or from
// the reflector.
function esc(s) {
  return String(s ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// QRZ link for a call sign, using only its base part (no suffix or module)
function qrzLink(call) {
  const base = String(call ?? '').replace(/[^A-Za-z0-9].*$/, '');
  if (!base) return esc(call);
  return `<a href="https://www.qrz.com/db/${encodeURIComponent(base)}" target="_blank" rel="noopener">${esc(call)}</a>`;
}
