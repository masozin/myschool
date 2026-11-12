// resources/js/Composables/useFormBuilder.js
import { reactive, computed } from 'vue'
import { router } from '@inertiajs/vue3'

let idCounter = -1

function defaultConfigFor(type) {
    if (['select', 'radio', 'checkbox'].includes(type)) {
        return { options: [{ label: 'Opsi 1', value: 'opsi_1' }] }
    }
    if (type === 'date') {
        return { date_format: 'DD/MM/YYYY' }
    }
    return {}
}

function makeField(type = 'text') {
    return {
        kind: 'field',
        id: null,
        type,
        name: `field_${Date.now().toString(36)}${idCounter}`,
        label: '',
        description: null,
        config: defaultConfigFor(type),
        is_required: false,
        _key: idCounter--,
        _collapsed: false,
        _hidden: false,
    }
}

function makeSection(title = 'Bagian Baru') {
    return {
        kind: 'section',
        id: null,
        title,
        description: '',
        _key: idCounter--,
        _collapsed: false,
    }
}

function normalizeField(f) {

    // Paksa config selalu object, bukan array kosong dari database lama
    const rawConfig = f.config
    const safeConfig = (rawConfig && !Array.isArray(rawConfig)) ? rawConfig : {}

    return {
        kind: 'field',
        id: f.id,
        type: f.type,
        name: f.name,
        label: f.label,
        description: f.description,
        config: safeConfig,
        is_required: f.is_required ?? false,
        sort_order: f.sort_order,
        _key: f.id,
        _collapsed: false,
        _hidden: false,
    }
}

/**
 * Menyusun ulang state.items dari data form:
 * sections dan loose fields digabung berdasarkan sort_order masing-masing
 * (keduanya memakai skala sort_order yang sama di backend),
 * lalu field di dalam tiap section disisipkan tepat setelah header section-nya.
 */
function buildInitialItems(form) {
    const sectionEntries = (form.sections ?? []).map(s => ({
        entry: {
            kind: 'section',
            id: s.id,
            title: s.title,
            description: s.description,
            _key: `s-${s.id}`,
            _collapsed: false,
        },
        sort_order: s.sort_order,
        childFields: (s.fields ?? [])
            .slice()
            .sort((a, b) => a.sort_order - b.sort_order)
            .map(normalizeField),
    }))

    const looseEntries = (form.fields ?? []).map(f => ({
        entry: normalizeField(f),
        sort_order: f.sort_order,
        childFields: null,
    }))

    const topLevel = [...sectionEntries, ...looseEntries].sort((a, b) => a.sort_order - b.sort_order)

    const items = []
    topLevel.forEach(({ entry, childFields }) => {
        items.push(entry)
        if (childFields) items.push(...childFields)
    })
    return items
}

export function useFormBuilder(initialForm) {
    const state = reactive({
        items: buildInitialItems(initialForm),
        saving: false,
        dirty: false,
    })

    function markDirty() { state.dirty = true }

    // ---- Insert & remove pada array tunggal ----

    function insertFieldAt(index, type = 'text') {
        const field = makeField(type)
        state.items.splice(index, 0, field)
        markDirty()
        return field
    }

    function insertSectionAt(index) {
        const section = makeSection(`Bagian ${state.items.filter(i => i.kind === 'section').length + 1}`)
        state.items.splice(index, 0, section)
        markDirty()
        return section
    }

    function removeItem(item) {
        const idx = state.items.indexOf(item)
        if (idx > -1) state.items.splice(idx, 1)
        markDirty()
        // Catatan: menghapus section HANYA menghapus header-nya.
        // Field di bawahnya tetap ada, otomatis jadi milik section sebelumnya (atau lepas kalau tidak ada).
    }

    function duplicateField(field) {
        const idx = state.items.indexOf(field)
        const clone = {
            ...JSON.parse(JSON.stringify(field)),
            id: null,
            _key: idCounter--,
            name: `${field.name}_copy`,
        }
        state.items.splice(idx + 1, 0, clone)
        markDirty()
    }

    function moveItem(item, direction) {
        const idx = state.items.indexOf(item)
        const targetIdx = direction === 'up' ? idx - 1 : idx + 1
        if (targetIdx < 0 || targetIdx >= state.items.length) return
        const [moved] = state.items.splice(idx, 1)
        state.items.splice(targetIdx, 0, moved)
        markDirty()
    }

    // ---- Metadata render: sectionKey pemilik & status collapse warisan dari section ----

    const renderItems = computed(() => {
        let currentSectionKey = null
        let currentSectionCollapsed = false

        return state.items.map((item, index) => {
            if (item.kind === 'section') {
                currentSectionKey = item._key
                currentSectionCollapsed = item._collapsed
                return { item, index, sectionKey: null, hiddenByCollapse: false }
            }
            return { item, index, sectionKey: currentSectionKey, hiddenByCollapse: currentSectionCollapsed }
        })
    })

    const totalFieldCount = computed(() => state.items.filter(i => i.kind === 'field').length)

    // ---- Serialisasi ke payload backend (bentuk sama seperti sebelumnya, backend tidak perlu berubah) ----

    function toPayload() {
        const sections = []
        const fields = []
        let currentSection = null
        let topLevelOrder = 0
        let sectionFieldOrder = 0

        const strip = (f) => ({
            id: f.id, type: f.type, name: f.name, label: f.label,
            description: f.description, config: f.config, is_required: f.is_required,
        })

        state.items.forEach(item => {
            if (item.kind === 'section') {
                currentSection = {
                    id: item.id, title: item.title, description: item.description,
                    sort_order: topLevelOrder++, fields: [],
                }
                sections.push(currentSection)
                sectionFieldOrder = 0
            } else if (currentSection) {
                currentSection.fields.push({ ...strip(item), sort_order: sectionFieldOrder++ })
            } else {
                fields.push({ ...strip(item), sort_order: topLevelOrder++ })
            }
        })

        return { sections, fields }
    }

    function save(formId, options = {}) {
        state.saving = true
        router.put(route('formulir.kelola.structure', formId), toPayload(), {
            preserveScroll: true,
            onSuccess: () => { state.dirty = false; options.onSuccess?.() },
            onFinish: () => { state.saving = false },
        })
    }

    return {
        state, renderItems, totalFieldCount,
        insertFieldAt, insertSectionAt, removeItem, duplicateField, moveItem, save,
    }
}
