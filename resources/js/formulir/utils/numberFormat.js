// resources/js/utils/numberFormat.js

export function parseFormatPattern(pattern) {
    if (!pattern) return null

    const match = pattern.match(/^([^\d#]*)([#0,.]+)([^\d#]*)$/)
    if (!match) return null

    const [, prefix, numberPart, suffix] = match

    const lastComma = numberPart.lastIndexOf(',')
    const lastDot = numberPart.lastIndexOf('.')
    const lastSepIndex = Math.max(lastComma, lastDot)

    let thousandSep = '', decimalSep = '', decimals = 0

    if (lastSepIndex !== -1) {
        decimalSep = numberPart[lastSepIndex]
        decimals = numberPart.length - lastSepIndex - 1
        thousandSep = decimalSep === ',' ? '.' : ','
        const integerPart = numberPart.slice(0, lastSepIndex)
        if (!integerPart.includes(thousandSep)) thousandSep = ''
    } else {
        thousandSep = numberPart.includes(',') ? ',' : (numberPart.includes('.') ? '.' : '')
    }

    return { prefix, suffix, thousandSep, decimalSep, decimals }
}

export function formatNumber(rawValue, pattern) {
    const config = parseFormatPattern(pattern)
    if (!config || rawValue === '' || rawValue === null || isNaN(rawValue)) return rawValue ?? ''

    const num = Number(rawValue)
    const fixed = config.decimals > 0 ? num.toFixed(config.decimals) : Math.round(num).toString()
    const [intPart, decPart] = fixed.split('.')

    const groupedInt = config.thousandSep
        ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, config.thousandSep)
        : intPart

    let result = groupedInt
    if (config.decimals > 0) result += config.decimalSep + decPart

    return `${config.prefix}${result}${config.suffix}`
}

export function unformatNumber(displayValue, pattern) {
    const config = parseFormatPattern(pattern)
    if (!config) return displayValue

    let str = String(displayValue)
    if (config.prefix) str = str.replace(config.prefix, '')
    if (config.suffix) str = str.replace(config.suffix, '')
    if (config.thousandSep) str = str.split(config.thousandSep).join('')
    if (config.decimalSep) str = str.replace(config.decimalSep, '.')

    const num = parseFloat(str)
    return isNaN(num) ? '' : num
}
