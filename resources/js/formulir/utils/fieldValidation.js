// resources/js/utils/fieldValidation.js

function checkContains(value, pattern) {
    const v = (value ?? '').toLowerCase()
    const orGroups = pattern.split('|')
    return orGroups.some(group => {
        const andTerms = group.split(';').map(t => t.trim()).filter(Boolean)
        if (andTerms.length === 0) return false
        return andTerms.every(term => v.includes(term.toLowerCase()))
    })
}

function findForbiddenGroup(value, pattern) {
    const v = (value ?? '').toLowerCase()
    const orGroups = pattern.split('|')
    for (const group of orGroups) {
        const andTerms = group.split(';').map(t => t.trim()).filter(Boolean)
        if (andTerms.length === 0) continue
        if (andTerms.every(term => v.includes(term.toLowerCase()))) return group
    }
    return null
}

function operatorLabel(op) {
    return { equals: 'sama dengan', not_equals: 'tidak sama dengan', gt: 'lebih dari', lt: 'kurang dari', between: 'di antara', not_between: 'bukan di antara' }[op] ?? op
}

/**
 * Validasi satu field, mengembalikan pesan error (string) atau null jika valid.
 * Meniru urutan & logic FormValidationRuleBuilder di backend.
 */
export function validateField(field, value) {
    const isEmpty = Array.isArray(value) ? value.length === 0 : (value === null || value === undefined || value === '')

    if (field.is_required && isEmpty) {
        return `${field.label} wajib diisi.`
    }

    if (isEmpty) return null // field kosong & tidak wajib -> lewati validasi lain

    if (field.type === 'email') {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!re.test(value)) return `${field.label} harus berupa email yang valid.`
    }

    if (field.type === 'number' && isNaN(Number(value))) {
        return `${field.label} harus berupa angka.`
    }

    const validation = field.config?.validation
    if (!validation) return null

    if (['text', 'textarea'].includes(field.type)) {
        for (const rule of validation.rules ?? []) {
            if (rule.type === 'length') {
                if (rule.min && value.length < rule.min) {
                    return `${field.label} minimal ${rule.min} karakter.`
                }
                if (rule.max && value.length > rule.max) {
                    return `${field.label} maksimal ${rule.max} karakter.`
                }
            }
            if (rule.type === 'contains' && rule.value) {
                if (!checkContains(value, rule.value)) {
                    return `${field.label} harus mengandung kata yang sesuai (${rule.value.replace(/;/g, ' dan ').replace(/\|/g, ' atau ')}).`
                }
            }
            if (rule.type === 'not_contains' && rule.value) {
                const found = findForbiddenGroup(value, rule.value)
                if (found) {
                    return `${field.label} tidak boleh mengandung "${found.replace(/;/g, ' dan ')}".`
                }
            }
        }
    }

    if (['number', 'date'].includes(field.type) && validation.operator) {
        const cast = (v) => field.type === 'date' ? new Date(v).getTime() : Number(v)
        const a = cast(value)
        const op = validation.operator

        const fail = (msg) => msg

        if (op === 'equals' && a !== cast(validation.value)) {
            return fail(`${field.label} harus sama dengan ${validation.value}.`)
        }
        if (op === 'not_equals') {
            const forbidden = (validation.values ?? '').split(';').map(v => v.trim()).filter(Boolean)
            if (forbidden.some(v => cast(v) === a)) {
                return fail(`${field.label} tidak boleh bernilai ${value}.`)
            }
        }
        if (op === 'gt' && !(a > cast(validation.value))) {
            return fail(`${field.label} harus lebih dari ${validation.value}.`)
        }
        if (op === 'lt' && !(a < cast(validation.value))) {
            return fail(`${field.label} harus kurang dari ${validation.value}.`)
        }
        if (op === 'between' && !(a >= cast(validation.value) && a <= cast(validation.value2))) {
            return fail(`${field.label} harus di antara ${validation.value} dan ${validation.value2}.`)
        }
        if (op === 'not_between' && (a >= cast(validation.value) && a <= cast(validation.value2))) {
            return fail(`${field.label} tidak boleh di antara ${validation.value} dan ${validation.value2}.`)
        }
    }

    if (field.type === 'file' && value instanceof File) {
        if (validation.max_size && value.size > validation.max_size * 1024 * 1024) {
            return `Ukuran file maksimal ${validation.max_size}MB.`
        }
        if (validation.extensions?.length) {
            const ext = value.name.split('.').pop().toLowerCase()
            if (!validation.extensions.includes(ext)) {
                return `Jenis file harus salah satu dari: ${validation.extensions.join(', ').toUpperCase()}.`
            }
        }
    }

    return null
}
