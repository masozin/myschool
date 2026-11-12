// resources/js/utils/dateFormat.js

const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember']
const MONTHS_SHORT_ID = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']
const DAYS_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']

function pad(n) { return String(n).padStart(2, '0') }

/**
 * Format objek Date menjadi string sesuai pola.
 * Token yang didukung: YYYY, YY, MM, M, DD, D, MMMM, MMM, dddd
 * Contoh pola: "DD/MM/YYYY", "D MMMM YYYY", "dddd, D MMMM YYYY"
 */
export function formatDate(date, pattern) {
    if (!date || !pattern) return ''
    const d = date instanceof Date ? date : new Date(date)
    if (isNaN(d.getTime())) return ''

    const map = {
        YYYY: d.getFullYear(),
        YY: String(d.getFullYear()).slice(-2),
        MMMM: MONTHS_ID[d.getMonth()],
        MMM: MONTHS_SHORT_ID[d.getMonth()],
        MM: pad(d.getMonth() + 1),
        M: d.getMonth() + 1,
        DD: pad(d.getDate()),
        D: d.getDate(),
        dddd: DAYS_ID[d.getDay()],
    }

    // Urutan panjang ke pendek supaya tidak salah replace (MMMM sebelum MM, dst)
    return pattern.replace(/YYYY|YY|MMMM|MMM|MM|M|DD|D|dddd/g, (token) => map[token])
}

/**
 * Parse string ISO (yyyy-mm-dd dari <input type="date"> atau value tersimpan)
 * menjadi Date object lokal, aman dari masalah timezone.
 */
export function parseISO(isoString) {
    if (!isoString) return null
    const [y, m, d] = isoString.split('-').map(Number)
    if (!y || !m || !d) return null
    return new Date(y, m - 1, d)
}

/**
 * Date object -> string ISO yyyy-mm-dd, untuk disimpan/dikirim ke server.
 */
export function toISO(date) {
    if (!date) return ''
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

export { MONTHS_ID, DAYS_ID }
