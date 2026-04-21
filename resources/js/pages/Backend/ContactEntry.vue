<script setup lang="ts">
import { ref, watch, onMounted, computed } from "vue";
import axios from "axios";
import { Head } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import DataTable from "@/Components/DataTable.vue";
import DesignationEntryModal from "@/components/DesignationEntryModal.vue"
import LeadSourceEntryModal from "@/components/LeadSourceEntryModal.vue";
import ShopTypeEntryModal from "@/components/ShopTypeEntryModal.vue";
import LocationEntryModal from "@/components/LocationEntryModal.vue";
import InterestLevelEntryModal from "@/components/InterestLevelEntryModal.vue";
import ServiceTypeEntryModal from "@/components/ServiceTypeEntryModal.vue";
import OfferEntryModal from "@/components/OfferConnectEntryModal.vue"
import Card from "primevue/card";
import Button from "primevue/button";
import Toast from "primevue/toast";
import { useToast } from "primevue/usetoast";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";
import Checkbox from "primevue/checkbox";
import Calendar from "primevue/calendar";
import Dropdown from "primevue/dropdown";
import Dialog from "primevue/dialog";
import Editor from "primevue/editor";
import Multiselect from "vue-multiselect";

const props = defineProps({
    userRole: String,
    userId: Number
});

const toast = useToast();
const showDesignationModal = ref(false);
const showLeadSourceModal = ref(false);
const showShopTypeModal = ref(false);
const showLocationModal = ref(false);
const showInterestLevelModal = ref(false);
const showServiceTypeModal = ref(false);
const showOfferModal = ref(false); // show/hide modal
const selectedCountry = ref<any | null>(null);

const customer = ref({
    name: "",
    designation: null,
    numbers: [],
    newNumber: "",
    newCountry: null,
    newNumberType: "call",
    email: "",
    shopType: null,
    location: null,
    leadSource: null,
    interestLevel: null,
    nextFollowUpDate: null,

    // 🔴 MATCH BACKEND
    last_discuss_note: "",

    serviceTypeLevel: [],
    feature_need: "",
    our_commitment: "",
    offerConnect: null,
    client_behaviour: "",

    status: "New",
    createdBy: props.userId,
    lastContactDate: null,
});

// Modal states
const showHistoryModal = ref(false);
const modalTitle = ref("");
const modalContent = ref("");

const showPreviewModal = ref(false);
const previewTitle = ref("");
const previewContent = ref("");

const openModal = (title: string, content: string) => {
    previewTitle.value = title;
    previewContent.value = content?.trim() || "";
    showPreviewModal.value = true;
};

// Modal state
const showExtraNoteModal = ref(false);
const noteCustomerId = ref<number | null>(null);
const pendingEditCustomer = ref<any | null>(null);
const newNote = ref("");
const customerHistory = ref<any[]>([]);

const editingNoteId = ref<number | null>(null);
const editingContent = ref("");


// Open Note Modal
const openNoteModal = async (customer: any) => {
    noteCustomerId.value = customer.id;
    newNote.value = "";

    await fetchLatestNotes();

    showExtraNoteModal.value = true;
};

const fetchLatestNotes = async () => {
    if (!noteCustomerId.value) return;

    try {
        const { data } = await axios.get(
            `/api/customers/${noteCustomerId.value}/notes`
        );

        // always keep last 2 only
        customerHistory.value = data.slice(0, 2);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to refresh notes',
            life: 3000
        });
    }
};

// Save Note
const saveNote = async () => {
    if (!noteCustomerId.value || !newNote.value.trim()) return;

    try {
        await axios.post(`/api/customers/${noteCustomerId.value}/add-note`, {
            note: newNote.value
        });

        toast.add({ severity: 'success', summary: 'Success', detail: 'Note saved', life: 2000 });

        // Add the new note locally to display instantly
        customerHistory.value.unshift({
            note: newNote.value,
            created_at: new Date(),
            staff: { name: user.value?.name || 'Staff' }
        });

        newNote.value = ''; // clear textarea
        await fetchLatestNotes();
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save note', life: 3000 });
    }
};

const startEdit = (note: any) => {
    editingNoteId.value = note.id;
    editingContent.value = note.note;
};

const updateNote = async () => {
    if (!editingNoteId.value || !editingContent.value.trim()) return;

    try {
        await axios.put(`/api/customer-history/${editingNoteId.value}/update-note`, {
            note: editingContent.value
        });

        // Update local history
        const note = customerHistory.value.find(n => n.id === editingNoteId.value);
        if (note) note.note = editingContent.value;

        // Reset editing state
        editingNoteId.value = null;
        editingContent.value = "";

        // Close modal
        showExtraNoteModal.value = false;

        // Clear new note input (if you also want to reset for adding)
        newNote.value = '';

        // Success toast
        toast.add({
            severity: 'success',
            summary: 'Updated',
            detail: 'Note updated successfully',
            life: 2000
        });

        // Refetch latest notes
        await fetchLatestNotes();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update note',
            life: 3000
        });
    }
};

const historyData = ref<any[]>([]);

const openHistoryModal = async (customer: any) => {
    try {
        const { data } = await axios.get(`/api/customers/${customer.id}/history`);
        modalTitle.value = `History of ${customer.name}`;
        historyData.value = data; // store raw array
        showHistoryModal.value = true;
    } catch (error) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to fetch history', life: 3000 });
    }
};

const searchQuery = ref("");
const searchResults = ref<any[]>([]);
const searching = ref(false);

const isEditMode = ref(false);
const editingCustomerId = ref<number | null>(null);

// 🔹 Debounce timer
let debounceTimer: ReturnType<typeof setTimeout> | null = null;

const searchCustomer = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    searching.value = true;

    try {
        const { data } = await axios.get('/api/customers/search', {
            params: { q: searchQuery.value }
        });
        searchResults.value = data.map((c: any) => ({
            ...c,

            // ✅ ALWAYS normalize service_type
            service_type: (() => {
                try {
                    return Array.isArray(c.service_type)
                        ? c.service_type
                        : JSON.parse(c.service_type || "[]");
                } catch {
                    return [];
                }
            })(),
        }));
    } catch (e) {
        console.error(e);
    } finally {
        searching.value = false;
    }
};

// 🔹 Watch with debounce
watch(searchQuery, (newVal) => {
    if (debounceTimer) clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        searchCustomer();
    }, 500); // 3000ms = 3 seconds
});

const resetSearch = () => {
    searchQuery.value = "";
    searchResults.value = [];
};

const handleLeadSourceCreated = (newSource: any) => {
    const option = { label: newSource.name, value: newSource.id };
    leadSourceOptions.value.push(option);
    customer.value.leadSource = option;
};

const handleShopTypeCreated = (newType: any) => {
    const option = { label: newType.name, value: newType.id };
    shopTypes.value.push(option);
    customer.value.shopType = option;
};

const handleLocationCreated = (newLoc: any) => {
    const option = {
        label: newLoc.area_name,
        value: newLoc.area_name
    }
    areas.value.push(option) // add to dropdown
    customer.value.location = option // auto select
}

const handleDesignationCreated = (newData: any) => {
    const option = { label: newData.designation_name, value: newData.id };
    designations.value.push(option);
    customer.value.designation = option;
};

const handleInterestLevelCreated = (newLevel: any) => {
    const option = { label: newLevel.level_name, value: newLevel.id };
    interestLevelOptions.value.push(option);
    customer.value.interestLevel = option;
};

const handleServiceTypeCreated = (newLevel: any) => {
    const option = { label: newLevel.service_type_name, value: newLevel.id };
    serviceTypeOptions.value.push(option);
    customer.value.serviceType = option;
};

const handleOfferCreated = (newOffer: any) => {
    const option = { label: newOffer.name, value: newOffer.id };
    offerOptions.value.push(option);
    customer.value.offerConnect = option;
};

const designations = ref<any[]>([]);

const fetchDesignations = async () => {
    try {
        const { data } = await axios.get("/api/designations");
        designations.value = data
            .filter((d: any) => d.status !== "Disabled")  // ✅ exclude Disabled
            .map((d: any) => ({
                label: d.designation_name,
                value: d.id
            }));
    } catch (err) {
        console.error(err);
    }
};

const shopTypes = ref<any[]>([]); // Store running shop types

const fetchShopTypes = async () => {
    try {
        const { data } = await axios.get("/api/shop-types");

        shopTypes.value = data
            .filter((st: any) => st.status !== "Disabled")
            .map((st: any) => ({
                label: st.name,
                value: st.id
            }));
    } catch (error) {
        console.error("Failed to fetch shop types:", error);
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to load shop types",
            life: 3000
        });
    }
};

// ================= COUNTRY (DB CRUD) =================
const countries = ref<any[]>([])

const newCountry = ref({
    name: "",
    status: "Running"
})

const editingCountryId = ref<number | null>(null)
const showCountryModal = ref(false)

// fetch countries from DB
const fetchCountriesFromDB = async () => {
    try {
        const { data } = await axios.get("/api/countries")
        countries.value = data
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "Failed to fetch countries",
            life: 3000
        })
    }
}

// open add modal
const openAddCountryModal = () => {
    editingCountryId.value = null
    newCountry.value = { name: "", status: "Running" }
    showCountryModal.value = true
}

// open edit modal
const openEditCountryModal = (country: any) => {
    editingCountryId.value = country.id
    newCountry.value = {
        name: country.name,   // from table row (already mapped)
        status: country.status
    }
    scrollTo(0, 0);
    showCountryModal.value = true
}

// save or update
const saveCountry = async () => {
    if (!newCountry.value.name.trim()) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Country name required",
            life: 2000
        })
        return
    }

    // 🔥 MAP FRONTEND FIELD TO BACKEND FIELD
    const payload = {
        country_name: newCountry.value.name, // ✅ important
        status: newCountry.value.status
    }

    try {
        if (editingCountryId.value) {
            await axios.put(`/api/countries/${editingCountryId.value}`, payload)
            toast.add({ severity: "success", summary: "Updated", detail: "Country updated", life: 2000 })
        } else {
            await axios.post("/api/countries", payload)
            toast.add({ severity: "success", summary: "Created", detail: "Country added", life: 2000 })
        }

        showCountryModal.value = false
        await fetchCountriesFromDB()
    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Operation failed",
            life: 3000
        })
    }
}

// delete
const deleteCountry = async (id: number) => {
    try {
        await axios.delete(`/api/countries/${id}`)
        toast.add({ severity: "success", summary: "Deleted", detail: "Country deleted", life: 2000 })
        fetchCountriesFromDB()
    } catch {
        toast.add({ severity: "error", summary: "Error", detail: "Delete failed", life: 3000 })
    }
}

// close modal
const closeCountryModal = () => {
    showCountryModal.value = false
    newCountry.value = { name: "", status: "Running" }
    editingCountryId.value = null
}

const allAreas = ref<any[]>([]);   // all from API
const areas = ref<any[]>([]);      // filtered for dropdown

const fetchAreas = async () => {
    try {
        const { data } = await axios.get("/api/areas");

        allAreas.value = data.filter(
            (area: any) => area.status !== "Disabled"
        );

    } catch (error) {
        console.error("Failed to fetch areas:", error);
    }
};

watch(selectedCountry, (newCountry) => {
    if (!newCountry) {
        areas.value = [];
        customer.value.location = null;
        return;
    }

    areas.value = allAreas.value
        .filter(area => area.country_name === newCountry.country_name)
        .map(area => ({
            label: area.area_name,
            value: area.area_name
        }));

    // reset location if not valid
    if (!areas.value.find(a => a.value === customer.value.location?.value)) {
        customer.value.location = null;
    }

    fetchAreas();
});

// Lead Source Options
const leadSourceOptions = ref<any[]>([]);

// Fetch Lead Sources from API
const fetchLeadSources = async () => {
    try {
        const { data } = await axios.get("/api/lead-sources");
        // Only running lead sources
        leadSourceOptions.value = data
            .filter((ls: any) => ls.status !== "Disabled")
            .map((ls: any) => ({ label: ls.name, value: ls.id }));
    } catch (error) {
        console.error("Failed to fetch lead sources:", error);
        toast.add({ severity: "error", summary: "Error", detail: "Failed to load lead sources", life: 3000 });
    }
};

const serviceTypeOptions = ref<any[]>([]);

// Fetch interest levels
const fetchServiceTypes = async () => {
    try {
        const { data } = await axios.get("/api/service-types");
        serviceTypeOptions.value = data
            .filter((il: any) => il.status !== "Disabled")
            .map((il: any) => ({ label: il.service_type_name, value: il.id }));
    } catch (err) {
        console.error(err);
    }
};

const interestLevelOptions = ref<any[]>([]);

// Fetch interest levels
const fetchInterestLevels = async () => {
    try {
        const { data } = await axios.get("/api/interest-levels");
        interestLevelOptions.value = data
            .filter((il: any) => il.status !== "Disabled")
            .map((il: any) => ({ label: il.level_name, value: il.id }));
    } catch (err) {
        console.error(err);
    }
};

const offerOptions = ref<any[]>([]); // store running offers

// Fetch offers from backend
const fetchOffers = async () => {
    try {
        const { data } = await axios.get("/api/offer-connects");
        offerOptions.value = data
            .filter((o: any) => o.status !== "Disabled")
            .map((o: any) => ({ label: o.name, value: o.id }));
    } catch (err) {
        console.error(err);
        toast.add({ severity: "error", summary: "Error", detail: "Failed to load offers", life: 3000 });
    }
};

const searchResultsWithSN = computed(() =>
    tableData.value.map((c, index) => ({
        sn: index + 1,
        ...c,

        // numbers display
        numbers: c.numbers ?? [],
        numbers_text: c.numbers
            ?.map((n: any) => `${n.full_number} (${n.type})`)
            .join(", ") ?? '-',

        // ✅ formatted dates (SAFE)
        next_follow_up_date_formatted: c.next_follow_up_date
            ? formatDate(c.next_follow_up_date)
            : '-',

        last_contact_date_formatted: c.last_contact_date
            ? formatDate(c.last_contact_date)
            : '-',
    }))
);

const countryList = ref([]);
const loadingCountries = ref(true);

const fetchCountries = async () => {
    try {
        const { data } = await axios.get(
            "https://restcountries.com/v3.1/all?fields=name,cca2,idd,flags"
        );

        countryList.value = data
            .filter(c => c.idd?.root)
            .map(c => ({
                flagPng: c.flags?.png || "",
                flagEmoji: c.flags?.emoji || "",
                dial_code: c.idd.root + (c.idd.suffixes?.[0] || ""),
                cca2: c.cca2   // Needed for default country
            }))
            .sort((a, b) => a.dial_code.localeCompare(b.dial_code));

        // Default Bangladesh = country code "BD"
        customer.value.newCountry =
            countryList.value.find(c => c.cca2 === "BD") || null;

    } catch (error) {
        console.error(error);
    } finally {
        loadingCountries.value = false;
    }
};

const usedNumbers = ref(["+8801711111111", "+8801812345678"]); // example

const numberExists = ref(false);
const phoneError = ref<string | null>(null);
const matchedCustomer = ref<any | null>(null);

const phonePlaceholder = computed(() => {
    return customer.value.newCountry
        ? "XXXXXXXXXX"   // ← remove country code from placeholder
        : "Enter number";
});

let lastSearchToken = 0;

const formatNumber = async () => {
    if (!customer.value.newCountry) return;

    const currentToken = ++lastSearchToken;

    const countryCode = customer.value.newCountry.dial_code;
    let value = customer.value.newNumber.replace(/\D/g, "");

    // If cleared → HARD RESET & EXIT
    if (!value) {
        matchedCustomer.value = null;
        numberExists.value = false;
        phoneError.value = null;
        return;
    }

    // Bangladesh rule
    if (countryCode === "+880" && value.startsWith("0")) {
        value = value.replace(/^0+/, "");
        phoneError.value = "Bangladesh numbers must not start with 0";
    } else {
        phoneError.value = null;
    }

    customer.value.newNumber = value;
    const fullNumber = countryCode + value;

    // Local duplicate check
    numberExists.value = usedNumbers.value.includes(fullNumber);

    // 🔴 Only search backend if length is valid
    if (value.length < 6) {
        matchedCustomer.value = null;
        numberExists.value = false;
        return;
    }

    try {
        const { data } = await axios.get("/api/customers/search", {
            params: { q: value }
        });

        // 🛑 IGNORE OLD RESPONSES
        if (currentToken !== lastSearchToken) return;

        if (Array.isArray(data) && data.length > 0) {
            matchedCustomer.value = data[0];
            numberExists.value = true;
        } else {
            matchedCustomer.value = null;
            numberExists.value = false;
        }
    } catch (err) {
        if (currentToken !== lastSearchToken) return;
        matchedCustomer.value = null;
        numberExists.value = false;
    }
};

watch(
    () => customer.value.newNumber,
    (val) => {
        if (!val) {
            lastSearchToken++; // cancel pending async checks
            matchedCustomer.value = null;
            numberExists.value = false;
            phoneError.value = null;
        }
    }
);

const parsedServiceTypes = computed(() => {
    try {
        return matchedCustomer.value?.service_type
            ? JSON.parse(matchedCustomer.value.service_type)
            : [];
    } catch {
        return [];
    }
});


const countryLabel = option => option?.dial_code || "";

const countryCodeFilter = (option, search) => {
    if (!search) return true;
    return option.dial_code.toLowerCase().includes(search.toLowerCase());
};

watch(() => customer.newCountry, (val) => {
    if (!val) return;
    const match = countryList.value.find(c => c.dial_code === val.dial_code);
    if (match) customer.newCountry = match;
});

const users = ref<any[]>([]);

// ====================
// FETCH LOGGED-IN STAFF
// ====================
const fetchUsers = async () => {
    try {
        const { data } = await axios.get('/api/users');
        users.value = data; // assuming this returns an array of all staff
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch users',
            life: 3000,
        });
    }
};

const phoneRules: Record<string, { min: number; max: number; noLeadingZero?: boolean }> = {
    "+880": { min: 10, max: 10, noLeadingZero: true }, // Bangladesh
    "+91": { min: 10, max: 10 }, // India
    "+92": { min: 10, max: 10 }, // Pakistan
    "+1": { min: 10, max: 10 },  // USA / Canada
    "+44": { min: 10, max: 10 }, // UK
};

const validatePhoneNumber = (number: string, countryCode: string) => {
    const rule = phoneRules[countryCode];

    // Digits only
    if (!/^\d+$/.test(number)) {
        return "Only numbers are allowed";
    }

    // Country-specific rule
    if (rule) {
        if (number.length < rule.min || number.length > rule.max) {
            return `Phone number must be ${rule.min} digits`;
        }

        if (rule.noLeadingZero && number.startsWith("0")) {
            return "Number must not start with 0";
        }
    }
    // Fallback (E.164)
    else {
        if (number.length < 6 || number.length > 15) {
            return "Enter a valid phone number";
        }
    }

    return null; // valid
};

// Functions
const addNumberField = () => {
    const num = customer.value.newNumber.trim();
    const country = customer.value.newCountry;

    if (!country) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Select a country",
            life: 3000
        });
        return;
    }

    if (!num) {
        toast.add({
            severity: "warn",
            summary: "Warning",
            detail: "Number required",
            life: 3000
        });
        return;
    }

    // 🔴 BLOCK if already exists (from API or local)
    if (numberExists.value) {
        toast.add({
            severity: "error",
            summary: "Duplicate",
            detail: "This number is already used",
            life: 3000
        });
        return;
    }

    // ✅ COUNTRY-AWARE VALIDATION
    const error = validatePhoneNumber(num, country.dial_code);
    if (error) {
        toast.add({
            severity: "error",
            summary: "Invalid Number",
            detail: error,
            life: 3000
        });
        return;
    }

    const fullNumber = country.dial_code + num;

    // ✅ FIXED duplicate check (field name corrected)
    const exists = customer.value.numbers.some(
        n => n.full_number === fullNumber
    );

    if (exists) {
        toast.add({
            severity: "error",
            summary: "Duplicate",
            detail: "This number already exists",
            life: 3000
        });
        return;
    }

    // ✅ SAVE NUMBER
    customer.value.numbers.push({
        number: num,
        full_number: fullNumber,   // ✅ CORRECT KEY
        type: customer.value.newNumberType,
        country
    });

    // ✅ keep local used numbers in sync
    usedNumbers.value.push(fullNumber);

    // ✅ RESET EVERYTHING
    customer.value.newNumber = "";
    customer.value.newNumberType = null;
    matchedCustomer.value = null;
    numberExists.value = false;
};


const removeNumberField = (index: number) => {
    customer.value.numbers.splice(index, 1);
};

const validateEmail = (email: string) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

const toMySQL = (date: any) => {
    if (!date) return null;
    return new Date(date).toISOString().slice(0, 19).replace("T", " ");
};

const resetForm = () => {
    customer.value = {
        name: "",
        designation: null,
        numbers: [],
        newNumber: "",
        newCountry: null,
        newNumberType: "call",
        email: "",
        shopType: null,
        selectedCountry: null,
        location: null,
        leadSource: null,
        interestLevel: null,
        nextFollowUpDate: null,

        // 🔴 MATCH BACKEND
        last_discuss_note: "",

        serviceTypeLevel: [],
        feature_need: "",
        our_commitment: "",
        offerConnect: null,
        client_behaviour: "",

        status: "New",
        createdBy: props.userId,
        lastContactDate: null,
    };

    numberExists.value = false;
};

const editCustomer = (cust: any) => {
    isEditMode.value = true;
    editingCustomerId.value = cust.id;

    // ✅ 1. SET COUNTRY
    const foundCountry = countries.value.find(
        c => c.country_name === cust.country_name
    );
    selectedCountry.value = foundCountry || null;

    // ✅ 2. FILTER AREAS BY COUNTRY
    areas.value = allAreas.value
        .filter(area => area.country_name === selectedCountry.value?.country_name)
        .map(area => ({
            label: area.area_name,
            value: area.area_name
        }));

    // ✅ 3. SET LOCATION
    customer.value.location =
        areas.value.find(a => a.value === cust.locations) || null;

    // ✅ 4. OTHER FIELDS
    customer.value.name = cust.name;
    customer.value.designation =
        designations.value.find(d => d.label === cust.designation) || null;

    customer.value.numbers = cust.numbers.map((n: any) => ({
        number: n.number,
        full_number: n.full_number,
        type: n.type,
        country: countryList.value.find(c => c.dial_code === n.country_code)
    }));

    customer.value.email = cust.email;
    customer.value.shopType =
        shopTypes.value.find(s => s.label === cust.shop_type) || null;

    customer.value.leadSource =
        leadSourceOptions.value.find(l => l.label === cust.lead_source) || null;

    customer.value.interestLevel =
        interestLevelOptions.value.find(i => i.label === cust.interest_level) || null;

    customer.value.serviceTypeLevel = parseServiceType(cust.service_type)
        .map((label: string) =>
            serviceTypeOptions.value.find(s => s.label === label)
        )
        .filter(Boolean);

    customer.value.last_discuss_note = cust.last_discuss_note || "";
    customer.value.feature_need = cust.feature_need || "";
    customer.value.our_commitment = cust.our_commitment || "";
    customer.value.client_behaviour = cust.client_behaviour || "";
    customer.value.offerConnect =
        offerOptions.value.find(o => o.label === cust.offer_connect) || null;

    customer.value.nextFollowUpDate = cust.next_follow_up_date
        ? new Date(cust.next_follow_up_date)
        : null;

    customer.value.status = cust.status;
    customer.value.createdBy = props.userId;

    customer.value.newNumber = "";
    customer.value.newCountry =
        countryList.value.find(c => c.cca2 === "BD") || null;

    searchResults.value = [];
};

const submitForm = async () => {
    // 1️⃣ BASIC VALIDATION
    if (!customer.value.name) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Name is required", life: 3000 });
        return;
    }
    if (!customer.value.numbers.length) {
        toast.add({ severity: "warn", summary: "Warning", detail: "At least one number is required", life: 3000 });
        return;
    }
    if (!customer.value.shopType) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Shop Type is required", life: 3000 });
        return;
    }
    if (!selectedCountry.value) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Country is required", life: 3000 });
        return;
    }
    if (!customer.value.location) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Select at least one Location", life: 3000 });
        return;
    }
    if (!customer.value.leadSource) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Lead Source is required", life: 3000 });
        return;
    }
    if (!customer.value.interestLevel) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Interest Level is required", life: 3000 });
        return;
    }
    if (!customer.value.serviceTypeLevel) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Service Type is required", life: 3000 });
        return;
    }
    if (!customer.value.nextFollowUpDate) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Next Follow-Up Date is required", life: 3000 });
        return;
    }
    if (!customer.value.last_discuss_note) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Last Discuss Notes is required", life: 3000 });
        return;
    }
    if (!customer.value.our_commitment) {
        toast.add({ severity: "warn", summary: "Warning", detail: "Our Commitment is required", life: 3000 });
        return;
    }

    if (customer.value.email && !validateEmail(customer.value.email)) {
        toast.add({ severity: "error", summary: "Error", detail: "Invalid Email", life: 3000 });
        return;
    }

    // 2️⃣ PREPARE PAYLOAD
    const payload = {
        name: customer.value.name,
        designation: customer.value.designation?.label || null,

        numbers: customer.value.numbers.map(n => ({
            number: n.number,
            full_number: n.full_number,
            type: n.type,
            country_code: n.country.dial_code
        })),

        email: customer.value.email,
        shop_type: customer.value.shopType?.label || null,
        country_id: selectedCountry.value?.id || null,
        country_name: selectedCountry.value?.country_name || null,
        locations: customer.value.location?.label || "",
        lead_source: customer.value.leadSource?.label || null,
        interest_level: customer.value.interestLevel?.label || null,
        service_type: customer.value.serviceTypeLevel.map(s => s.label),

        next_follow_up_date: toMySQL(customer.value.nextFollowUpDate),

        // ✅ EXACT MATCH
        last_discuss_note: customer.value.last_discuss_note,
        feature_need: customer.value.feature_need,
        our_commitment: customer.value.our_commitment,
        client_behaviour: customer.value.client_behaviour,
        offer_connect: customer.value.offerConnect?.label || null,

        status: customer.value.status,
        created_by: customer.value.createdBy,
        last_contact_date: toMySQL(new Date()),
    };

    // 3️⃣ SEND API REQUEST
    try {
        if (isEditMode.value && editingCustomerId.value) {
            await axios.put(`/api/customers/${editingCustomerId.value}`, payload);

            toast.add({
                severity: "success",
                summary: "Updated",
                detail: "Customer updated successfully",
                life: 3000
            });
        } else {
            await axios.post("/api/customers", payload);

            toast.add({
                severity: "success",
                summary: "Created",
                detail: "Customer saved successfully",
                life: 3000
            });
        }

        resetForm();
        isEditMode.value = false;
        editingCustomerId.value = null;

    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Operation failed",
            life: 5000
        });
    }
};

const parseServiceType = (value: any): string[] => {
    if (Array.isArray(value)) return value;

    try {
        return JSON.parse(value || "[]");
    } catch {
        return [];
    }
};

onMounted(() => {
    fetchUsers();
    fetchCountries();
    fetchCountriesFromDB();
    fetchAreas();
    fetchLeadSources();
    fetchShopTypes();
    fetchServiceTypes();
    fetchDesignations();
    fetchInterestLevels();
    fetchOffers();
});

// Modal state for service type
const showUpdateServiceTypeModal = ref(false);
const editingServiceCustomer = ref<any | null>(null);
const serviceTypes = ref<string[]>([]);
const newServiceType = ref("");

// Open modal
const openServiceTypeModal = (customer: any) => {
    editingServiceCustomer.value = customer;

    // ✅ service_type is already an array
    serviceTypes.value = Array.isArray(customer.service_type)
        ? [...customer.service_type]
        : [];

    newServiceType.value = "";
    showUpdateServiceTypeModal.value = true;
};

// Add service type
const addServiceType = () => {
    const value = newServiceType.value.trim();
    if (value && !serviceTypes.value.includes(value)) {
        serviceTypes.value.push(value);
    }
    newServiceType.value = "";
};

// Save service types
const saveServiceTypes = async () => {
    if (!editingServiceCustomer.value) return;

    try {
        const { data } = await axios.put(
            `/api/customers/${editingServiceCustomer.value.id}/option/service-type`,
            {
                service_type: serviceTypes.value
            }
        );

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Service type updated successfully",
            life: 3000
        });

        // ✅ Close modal
        showUpdateServiceTypeModal.value = false;
        editingServiceCustomer.value = null;

        // 🔹 REFRESH SEARCH RESULTS
        if (searchQuery.value.length >= 2) {
            await searchCustomer(); // fetch latest table data with current search query
        }

    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update service type",
            life: 3000
        });
    }
};

// Modal state for Next Follow-Up update
const showFollowUpModal = ref(false);
const editingFollowUpCustomer = ref<any | null>(null);
const newFollowUpDate = ref<Date | null>(null);

// Open modal for specific row
const openFollowUpModal = (customer: any) => {
    editingFollowUpCustomer.value = customer;
    newFollowUpDate.value = customer.next_follow_up_date ? new Date(customer.next_follow_up_date) : null;
    showFollowUpModal.value = true;
};

const updateFollowUpDate = async () => {
    if (!editingFollowUpCustomer.value || !newFollowUpDate.value) {
        toast.add({
            severity: 'warn',
            summary: 'Warning',
            detail: 'Please select a date',
            life: 3000
        });
        return;
    }

    const customer = editingFollowUpCustomer.value;

    const formatDateLocal = (date: Date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    };

    try {
        const payload = {
            name: customer.name,
            created_by: customer.created_by,
            numbers: customer.numbers.map((n: any) => ({
                number: n.number,
                full_number: n.full_number
            })),
            next_follow_up_date: formatDateLocal(newFollowUpDate.value),
        };

        await axios.put(`/api/customers/${customer.id}`, payload);

        toast.add({
            severity: 'success',
            summary: 'Updated',
            detail: 'Next Follow-Up Date updated',
            life: 3000
        });

        showFollowUpModal.value = false;
        editingFollowUpCustomer.value = null;
        newFollowUpDate.value = null;

        // 🔹 REFRESH SEARCH RESULTS
        if (searchQuery.value.length >= 2) {
            await searchCustomer(); // fetch latest table data with current search query
        }

    } catch (error: any) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Update failed',
            life: 5000
        });
    }
};


// Staff Status Modal
const showStaffStatusModal = ref(false);
const editingStaffCustomer = ref<any | null>(null);
const selectedStaffStatus = ref("");

// Open modal
const openStaffStatusModal = (customer: any) => {
    editingStaffCustomer.value = customer;
    selectedStaffStatus.value = customer.staff_status || "";
    showStaffStatusModal.value = true;
};

// Save
const saveStaffStatus = async () => {
    if (!editingStaffCustomer.value) return;

    try {
        await updateStaffStatus(
            editingStaffCustomer.value.id,
            selectedStaffStatus.value
        );

        showStaffStatusModal.value = false;
    } catch {
        // ❌ DO NOTHING — toast already handled
    }
};

const updateStaffStatus = async (customerId: number, status: string) => {
    try {
        await axios.put(`/api/customers/${customerId}/staff-status`, {
            staff_status: status,
        });

        toast.add({
            severity: "success",
            summary: "Updated",
            detail: "Staff status updated successfully",
            life: 2000,
        });

        // ✅ UPDATE SEARCH RESULT TABLE
        const customer = searchResults.value.find(c => c.id === customerId);
        if (customer) {
            customer.staff_status = status;
        }

    } catch (error: any) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: error.response?.data?.message || "Failed to update staff status",
            life: 3000,
        });

        throw error; // ✅ VERY IMPORTANT
    }
};

const columns = [
    { key: "sn", label: "SN", align: "center" },
    { key: "name", label: "Name", align: "left" },
    { key: "service_type", label: "Service Type", align: "center" },
    { key: "next_follow_up_date", label: "Next Follow Up", align: "center" },
    { key: "staff_status", label: "Staff Status", align: "center", type: "select" },
    { key: "actions", label: "Actions", align: "center" },
    { key: "numbers", label: "Numbers", align: "center" },
    { key: "email", label: "Email", align: "left" },
    { key: "shop_type", label: "Shop Type", align: "center" },
    { key: "locations", label: "Locations", align: "center" },
    { key: "lead_source", label: "Lead Source", align: "center" },
    { key: "interest_level", label: "Interest", align: "center" },
    { key: "feature_need", label: "Feature Need", type: "modal" },
    { key: "our_commitment", label: "Our Commitment", type: "modal" },
    { key: "client_behaviour", label: "Client Behaviour", type: "modal" },
    { key: "last_discuss_note", label: "Last Discuss Note", type: "modal" },
    { key: "offer_connect", label: "Offer Connect", align: "center" },
    { key: "last_contact_date", label: "Last Contact", align: "center" },
    // NEW actions column
];

const getStaffName = (staffId: number | null, fallback = 'Staff') => {
    if (!staffId) return fallback;
    const staff = users.value.find(u => u.id === staffId);
    return staff ? staff.name : fallback;
};

const usersMap = computed(() => {
    const map: Record<number, any> = {};
    users.value.forEach(user => {
        map[user.id] = user;
    });
    return map;
});

const tableData = computed(() => {
    return searchResults.value.map(customer => {
        const staff = usersMap.value[customer.assigned_staff_id];

        return {
            ...customer,

            // ✅ MUST MATCH column.key
            assigned_staff: staff
                ? `${staff.name}`
                : '—',

            // ✅ overwrite original fields
            next_follow_up_date: customer.next_follow_up_date
                ? formatDate(customer.next_follow_up_date)
                : '-',

            last_contact_date: customer.last_contact_date
                ? formatDate(customer.last_contact_date)
                : '-',

        };
    });
});

// ====================
// UPDATE STAFF STATUS
// ====================
const staffStatusOptions = [
    "New",
    "Interested",
    "Serious Interested",
    "Call For Demo",
    "Need To Show Demo",
    "Need Direct Meeting",
    "Future",
    "Unwanted",
    "Cancelled",
    "Final Client",
];

const formatDate = (date: string | null) => {
    if (!date) return '-';

    return new Intl.DateTimeFormat('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    }).format(new Date(date));
};

const formatHistoryValue = (key: string, value: any) => {
    if (!value) return '-';

    // Date fields
    if (
        key === 'last_contact_date' ||
        key === 'next_follow_up_date'
    ) {
        return formatDate(value);
    }

    // HTML content (like client_behaviour)
    if (typeof value === 'string' && value.includes('<')) {
        return value;
    }

    return value;
};
</script>

<template>
    <AppLayout>

        <Head title="Customer Contact Entry" />
        <Toast />

        <div class="p-6">
            <Card>
                <template #title>
                    <h2 class="text-2xl font-bold text-gray-700">Add Customer Contact</h2>
                </template>

                <template #content>

                    <div class="flex justify-end w-full mt-4">
                        <div class="relative w-full mb-5 flex items-center justify-center gap-3">
                            <!-- Search input wrapper -->
                            <div class="relative flex justify-center">
                                <InputText v-model="searchQuery" placeholder="Search by name or phone" class="w-60 pr-10 py-3 rounded-xl border border-gray-300
                                    focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                                    shadow-sm transition" />

                                <!-- Search Icon -->
                                <i class="pi pi-search absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            </div>

                            <!-- Reset Button -->
                            <Button v-if="searchQuery" icon="pi pi-times" severity="secondary" outlined
                                class="rounded-xl h-[44px]" @click="resetSearch" />

                            <!-- Search Results Dropdown -->
                            <transition name="fade">
                                <div v-if="searchResults.length" class="absolute z-50 w-full mt-80 bg-white rounded-xl shadow-2xl
                                    border border-gray-200 max-h-[70vh] overflow-auto">
                                    <DataTable title="All Customers Lists" :columns="columns"
                                        :rows="searchResultsWithSN" :showSearch="true" @openModal="openModal">

                                        <!-- Name + Designation -->
                                        <template #cell-name="{ row }">
                                            <div class="flex flex-col">
                                                <span class="font-semibold text-gray-800">{{ row.name }}</span>
                                                <span class="text-sm text-gray-500">{{ row.designation || '-' }}</span>

                                                <!-- Assigned Staff -->
                                                <span class="text-xs text-blue-600 font-medium">
                                                    Assigned: {{ row.assigned_staff || 'Not Assigned' }}
                                                </span>
                                            </div>
                                        </template>


                                        <template #cell-service_type="{ row }">
                                            <div class="flex items-center justify-center gap-2">
                                                <div class="flex flex-col text-sm text-gray-700">
                                                    <span v-for="(type, idx) in row.service_type" :key="idx">
                                                        {{ type }}
                                                    </span>
                                                </div>

                                                <button @click="openServiceTypeModal(row)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    <i class="pi pi-plus"></i>
                                                </button>
                                            </div>
                                        </template>


                                        <template #cell-staff_status="{ row }">
                                            <div class="flex items-center justify-center gap-2">
                                                <span>{{ row.staff_status || '-' }}</span>
                                                <button @click="openStaffStatusModal(row)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    <i class="pi pi-pencil"></i>
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Numbers -->
                                        <template #cell-numbers="{ row }">
                                            <div class="flex flex-col text-sm text-gray-700">
                                                <span v-for="(num, idx) in row.numbers" :key="idx">
                                                    {{ num.fullNumber || num.number }} ({{ num.type }})
                                                </span>
                                            </div>
                                        </template>

                                        <!-- Next Follow-up Date -->
                                        <template #cell-next_follow_up_date="{ row }">
                                            <div class="flex items-center justify-center gap-2">
                                                <span class="text-sm text-gray-600">
                                                    {{
                                                        row.next_follow_up_date
                                                            ? new Intl.DateTimeFormat('en-GB', {
                                                                day: '2-digit', month: 'short', year: 'numeric'
                                                            }).format(new Date(row.next_follow_up_date))
                                                            : '-'
                                                    }}
                                                </span>
                                                <button @click="openFollowUpModal(row)"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    <i class="pi pi-plus"></i>
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Last Contact Date -->
                                        <template #cell-last_contact_date="{ row }">
                                            <span class="text-sm text-gray-600">
                                                {{
                                                    row.last_contact_date
                                                        ? new Intl.DateTimeFormat('en-GB', {
                                                            day: '2-digit', month: 'short', year: 'numeric'
                                                        }).format(new Date(row.last_contact_date))
                                                        : '-'
                                                }}
                                            </span>
                                        </template>

                                        <!-- History & Add Note Buttons -->
                                        <template #cell-actions="{ row }">
                                            <div class="flex flex-col gap-2">
                                                <!-- Top row: 2 buttons -->
                                                <div class="flex gap-2">
                                                    <button
                                                        class="flex items-center gap-1 px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                                        @click="openHistoryModal(row)">
                                                        <i class="pi pi-history"></i>
                                                    </button>

                                                    <button
                                                        class="flex items-center gap-1 px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                                                        @click="openNoteModal(row)">
                                                        <i class="pi pi-plus"></i>
                                                    </button>
                                                </div>

                                                <!-- Bottom row: edit button -->
                                                <div>
                                                    <Button icon="pi pi-pencil" class="p-button-rounded p-button-info"
                                                        @click="editCustomer(row)" />
                                                </div>
                                            </div>
                                        </template>

                                    </DataTable>
                                </div>
                            </transition>
                        </div>
                    </div>

                    <form @submit.prevent="submitForm"
                        class="space-y-6 bg-white p-6 rounded-xl shadow-lg w-full md:w-3/4 mx-auto">

                        <!-- 1️⃣ NAME & DESIGNATION (Top - No Change) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mt-1">
                                <label class="block mb-2 font-medium">Name <span class="text-red-600">*</span></label>
                                <InputText v-model="customer.name" placeholder="Enter full name"
                                    class="w-full border rounded-lg p-2" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Designation</label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showDesignationModal = true" />

                                </div>

                                <Multiselect v-model="customer.designation" :options="designations"
                                    placeholder="Select Designation" label="label" track-by="value" class="w-full" />
                            </div>
                        </div>

                        <!-- 2️⃣ NUMBER (Required – modern UX version) -->
                        <div>
                            <label class="block mb-1 font-semibold text-gray-700">
                                Numbers <span class="text-red-600">*</span>
                            </label>

                            <!-- INPUT AREA -->
                            <div class="flex items-center gap-3 mb-4 p-4 border rounded-xl shadow-sm bg-white">

                                <!-- COUNTRY SELECT -->
                                <Multiselect v-model="customer.newCountry" :options="countryList" :searchable="true"
                                    placeholder="" track-by="dial_code" :custom-label="countryLabel"
                                    :show-labels="false" :internal-search="true" :close-on-select="true"
                                    :allow-empty="true" class="min-w-26"
                                    style="width: 100px !important; min-width:100px !important;">
                                    <!-- OPTIONS -->
                                    <template #option="props">
                                        <div class="flex items-center gap-2 py-1">
                                            <img v-if="props.option.flagPng" :src="props.option.flagPng"
                                                class="w-4 h-3 rounded-sm" />
                                            <span class="font-semibold text-sm">{{ props.option.dial_code }}</span>
                                        </div>
                                    </template>

                                    <!-- SELECTED -->
                                    <template #singleLabel="props">
                                        <div class="flex items-center gap-1">
                                            <img v-if="props.option.flagPng" :src="props.option.flagPng"
                                                class="w-5 h-3 rounded-sm" />
                                            <span class="font-semibold text-sm">{{ props.option.dial_code }}</span>
                                        </div>
                                    </template>

                                    <template #caret><span></span></template>
                                    <template #clear><span></span></template>
                                </Multiselect>

                                <!-- NUMBER INPUT -->
                                <div class="w-full">
                                    <InputText v-model="customer.newNumber" :placeholder="phonePlaceholder"
                                        @input="formatNumber" class="flex-1 p-3 w-full rounded-xl shadow-inner" />

                                    <p v-if="phoneError" class="text-red-500 text-sm mt-1">
                                        {{ phoneError }}
                                    </p>

                                    <p v-else-if="customer.newNumber && numberExists" class="text-red-500 text-sm mt-1">
                                        This number is already used!
                                    </p>
                                </div>

                                <!-- TYPE DROPDOWN -->
                                <Dropdown v-model="customer.newNumberType" :options="[
                                    { label: 'Call', value: 'call' },
                                    { label: 'WhatsApp', value: 'whatsapp' },
                                    { label: 'Both', value: 'both' }
                                ]" optionLabel="label" optionValue="value" class="w-32" placeholder="Type" />

                                <!-- ADD BUTTON -->
                                <Button icon="pi pi-plus" class="p-button-success shadow px-4"
                                    :disabled="!!phoneError || numberExists || !customer.newNumber"
                                    @click="addNumberField" />
                            </div>

                            <div v-if="matchedCustomer" class="overflow-x-auto mt-2">
                                <table class="min-w-full text-sm bg-white border rounded-lg shadow-sm">
                                    <thead class="bg-green-500 text-white">
                                        <tr>
                                            <th class="px-4 py-2 text-left border">Name</th>
                                            <th class="px-4 py-2 text-left border">Service Type</th>
                                            <th class="px-4 py-2 text-left border">Next Follow Up</th>
                                            <th class="px-4 py-2 text-left border">Last Note</th>
                                            <th class="px-4 py-2 text-left border">Action</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr class="border-t hover:bg-gray-50">

                                            <!-- Name + Staff Status -->
                                            <td class="px-4 py-2">
                                                <div class="font-semibold text-gray-900">
                                                    {{ matchedCustomer.name }}
                                                </div>
                                                <div class="text-xs text-blue-600 mt-1">
                                                    {{ matchedCustomer.staff_status }}
                                                </div>
                                            </td>

                                            <!-- Service Type -->
                                            <td class="px-4 py-2">
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="(type, idx) in parsedServiceTypes" :key="idx"
                                                        class="px-2 py-0.5 text-xs bg-gray-200 rounded">
                                                        {{ type }}
                                                    </span>
                                                </div>
                                            </td>

                                            <!-- Next Follow Up -->
                                            <td class="px-4 py-2">
                                                {{ formatDate(matchedCustomer.next_follow_up_date) || 'N/A' }}
                                            </td>

                                            <!-- Last Note -->
                                            <td class="px-4 py-2">
                                                {{ matchedCustomer.last_discuss_note || 'N/A' }}
                                            </td>

                                            <!-- Action -->
                                            <td class="px-4 py-2">
                                                <button
                                                    class="flex items-center gap-1 px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                                                    @click="openHistoryModal(matchedCustomer)">
                                                    <i class="pi pi-history"></i>
                                                    History
                                                </button>
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- SAVED NUMBERS LIST -->
                            <div v-for="(num, index) in customer.numbers" :key="index"
                                class="flex items-center justify-between gap-3 mb-2 p-3 rounded-lg border bg-white shadow-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-2xl">{{ num.country.flagEmoji }}</span>
                                    <span class="font-medium">{{ num.country.dial_code }} {{ num.number }}</span>
                                    <span class="px-2 py-0.5 text-xs bg-gray-100 rounded border capitalize">
                                        {{ num.type }}
                                    </span>
                                </div>

                                <Button icon="pi pi-trash" class="p-button-rounded p-button-danger p-button-text"
                                    @click="removeNumberField(index)" />
                            </div>
                        </div>

                        <!-- 3️⃣ SHOP TYPE & SERVICE TYPE -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Shop Type <span class="text-red-600">*</span></label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showShopTypeModal = true" />
                                </div>
                                <Multiselect v-model="customer.shopType" :options="shopTypes"
                                    placeholder="Select Shop Type" label="label" track-by="value" class="w-full" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Service Type <span class="text-red-600">*</span></label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showServiceTypeModal = true" />
                                </div>
                                <Multiselect v-model="customer.serviceTypeLevel" :options="serviceTypeOptions"
                                    placeholder="Select Service Type" label="label" track-by="value" :multiple="true"
                                    :close-on-select="false" :clear-on-select="false" class="w-full" />
                            </div>
                        </div>

                        <!-- 3️⃣ COUNTRY & LOCATION -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Country <span class="text-red-600">*</span></label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showCountryModal = true" />
                                </div>
                                <!-- COUNTRY -->
                                <Multiselect v-model="selectedCountry" :options="countries" label="country_name"
                                    track-by="id" placeholder="Select Country" class="mb-2" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Location <span class="text-red-600">*</span></label>
                                    <Button icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showLocationModal = true" />
                                </div>
                                <Multiselect v-model="customer.location" :options="areas" :multiple="false"
                                    placeholder="Select Location" label="label" track-by="value" class="w-full" />
                            </div>
                        </div>

                        <!-- 4️⃣ LEAD SOURCE & INTEREST LEVEL -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Lead Source <span class="text-red-600">*</span></label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showLeadSourceModal = true" />
                                </div>
                                <Multiselect v-model="customer.leadSource" :options="leadSourceOptions"
                                    placeholder="Select Lead Source" label="label" track-by="value" class="w-full" />
                            </div>

                            <div>
                                <div class="flex justify-between items-center mb-1">
                                    <label class="font-medium">Interest Level <span
                                            class="text-red-600">*</span></label>
                                    <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                        class="p-button-rounded p-button-sm p-button-success"
                                        @click="showInterestLevelModal = true" />
                                </div>
                                <Multiselect v-model="customer.interestLevel" :options="interestLevelOptions"
                                    placeholder="Select Interest Level" label="label" track-by="value" class="w-full" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1">
                            <!-- 5️⃣ FOLLOW-UP DATE -->
                            <div>
                                <label class="block mb-1 font-medium">Next Follow-Up Date <span
                                        class="text-red-600">*</span></label>
                                <Calendar v-model="customer.nextFollowUpDate" showIcon class="w-full" />
                            </div>
                        </div>


                        <!-- 6️⃣ LAST DISCUSS NOTES -->
                        <div>
                            <label class="block mb-1 font-medium">Last Discuss Notes <span
                                    class="text-red-600">*</span></label>
                            <Textarea v-model="customer.last_discuss_note" rows="3" :key="editingCustomerId"
                                class="w-full border rounded-lg p-2" />
                        </div>

                        <!-- 12️⃣ CLIENT BEHAVIOUR -->
                        <div class="mb-16">
                            <label class="block mb-1 font-medium">Client Behaviour & Sound <span
                                    class="text-red-600">*</span></label>
                            <Editor v-model="customer.client_behaviour" :key="editingCustomerId"
                                style="height: 140px;" />
                        </div>

                        <!-- 🔟 OUR COMMITMENT -->
                        <div class="mt-5">
                            <label class="block mb-1 font-medium">Our Commitment <span
                                    class="text-red-600">*</span></label>
                            <Textarea v-model="customer.our_commitment" rows="3" class="w-full border rounded-lg p-2" />
                        </div>

                        <!-- 8️⃣ EMAIL (Moved down to non-required section) -->
                        <div>
                            <label class="block mb-1 font-medium">Email</label>
                            <InputText v-model="customer.email" placeholder="Enter email address"
                                class="w-full border rounded-lg p-2" />
                        </div>

                        <!-- 9️⃣ FEATURE NEED -->
                        <div class="mb-10">
                            <label class="block mb-1 font-medium">Feature Need</label>
                            <Editor v-model="customer.feature_need" :key="'feature-' + editingCustomerId"
                                style="height: 150px;" />
                        </div>

                        <!-- 11️⃣ OFFER CONNECT -->
                        <div class="mt-16">
                            <div class="flex justify-between items-center mb-1">
                                <label class="font-medium">Which Offer Connect Me</label>
                                <Button v-if="props.userRole === 'admin'" icon="pi pi-plus"
                                    class="p-button-rounded p-button-sm p-button-success"
                                    @click="showOfferModal = true" />
                            </div>

                            <Multiselect v-model="customer.offerConnect" :options="offerOptions"
                                placeholder="Select Offer" label="label" track-by="value" class="w-full" />
                        </div>

                        <!-- SUBMIT & RESET -->
                        <div class="flex justify-center gap-4 mt-10">
                            <Button type="button" label="Reset" icon="pi pi-refresh" class="p-button-secondary w-1/4"
                                @click="resetForm" />

                            <Button type="submit" :label="isEditMode ? 'Update Customer' : 'Save Customer'"
                                icon="pi pi-check" class="p-button-success w-1/3" />
                        </div>

                    </form>
                </template>
            </Card>
        </div>
    </AppLayout>

    <Dialog v-model:visible="showCountryModal" header="Country" :modal="true" :style="{ width: '40rem' }">
        <div class="flex flex-col gap-3">

            <div class="flex justify-center">
                <div class="grid grid-cols-1 gap-3 mb-4 w-full max-w-sm">
                    <InputText v-model="newCountry.name" placeholder="Country Name" />

                    <Multiselect v-model="newCountry.status" :options="['Running', 'Disabled']" />

                    <Button :label="editingCountryId ? 'Update' : 'Save'" icon="pi pi-check" class="p-button-success"
                        @click="saveCountry" />
                </div>
            </div>

        </div>

        <DataTable title="Countries" :columns="[
            { key: 'sn', label: 'SN', align: 'center' },
            { key: 'name', label: 'Country', align: 'left' },
            { key: 'status', label: 'Status', align: 'center' },
            { key: 'actions', label: 'Actions', align: 'center' }
        ]" :rows="countries.map((c, i) => ({
            sn: i + 1,
            id: c.id,
            name: c.country_name,   // ✅ FIX HERE
            status: c.status
        }))" :onEdit="openEditCountryModal" :onDelete="deleteCountry" />
    </Dialog>

    <!-- ServiceType Modal -->
    <Dialog v-model:visible="showUpdateServiceTypeModal" header="Edit Service Types" modal :style="{ width: '30rem' }">
        <div class="flex flex-col gap-3">
            <div class="flex gap-2">
                <input v-model="newServiceType" placeholder="Enter service type"
                    class="flex-1 border rounded px-3 py-2 focus:ring-2 focus:ring-blue-400" />
                <button @click="addServiceType"
                    class="bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700 transition">Add</button>
            </div>

            <div v-if="serviceTypes.length" class="flex flex-col gap-2">
                <div v-for="(s, idx) in serviceTypes" :key="idx"
                    class="flex justify-between items-center bg-gray-100 px-3 py-2 rounded">
                    <span>{{ s }}</span>
                    <button @click="serviceTypes.splice(idx, 1)" class="text-red-500 hover:text-red-700">
                        <i class="pi pi-times"></i>
                    </button>
                </div>
            </div>
            <div v-else class="text-gray-500">No service types added</div>
        </div>

        <div class="text-right mt-4 flex gap-2 justify-end">
            <button class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                @click="showUpdateServiceTypeModal = false">
                Cancel
            </button>
            <button class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="saveServiceTypes">
                Save
            </button>
        </div>
    </Dialog>

    <!-- Update Next Follow-Up Date Modal -->
    <Dialog v-model:visible="showFollowUpModal" modal header="Update Next Follow-Up Date" :style="{ width: '30rem' }">
        <div class="flex flex-col gap-4">
            <label class="font-medium text-gray-700">Select New Date:</label>
            <Calendar v-model="newFollowUpDate" date-format="yy-mm-dd" :show-icon="true" class="w-full" />
        </div>

        <div class="text-right mt-6 flex gap-2 justify-end">
            <button
                class="px-5 py-2 bg-gray-300 text-gray-700 font-medium rounded-md shadow hover:bg-gray-400 transition"
                @click="showFollowUpModal = false">
                Cancel
            </button>

            <button class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                @click="updateFollowUpDate">
                Save
            </button>
        </div>
    </Dialog>

    <!-- Staff Status Update Modal -->
    <Dialog v-model:visible="showStaffStatusModal" header="Update Staff Status" modal :style="{ width: '30rem' }">
        <div class="flex flex-col gap-4">
            <label class="font-medium">Select Status:</label>
            <select v-model="selectedStaffStatus" class="border rounded px-3 py-2">
                <option v-for="status in staffStatusOptions" :key="status" :value="status">
                    {{ status }}
                </option>
            </select>
        </div>

        <div class="text-right mt-6 flex gap-2 justify-end">
            <button class="px-5 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400"
                @click="showStaffStatusModal = false">
                Cancel
            </button>
            <button class="px-5 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" @click="saveStaffStatus">
                Save
            </button>
        </div>
    </Dialog>

    <!-- PREVIEW MODAL -->
    <Dialog v-model:visible="showPreviewModal" modal :style="{ width: '40rem' }">

        <h2 class="text-xl font-semibold text-gray-900 mb-4">
            {{ previewTitle }}
        </h2>

        <!-- Glass Card Wrapper -->
        <div v-if="previewContent && previewContent.trim()" class="
            relative overflow-hidden
            rounded-lg prose prose-gray
            bg-white/30 backdrop-blur-xl
            border-l-4 border-blue-500
            shadow-lg shadow-black/10
        ">
            <!-- Glow Layer -->
            <span
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/40 via-transparent to-transparent">
            </span>

            <!-- HTML Content -->
            <div v-html="previewContent"
                class="relative p-6 text-gray-800 leading-relaxed whitespace-pre-line max-w-none">
            </div>
        </div>

        <!-- No Data Message -->
        <div v-else class="
            flex flex-col items-center justify-center
            p-8 rounded-lg
            bg-gray-50 border border-dashed
            text-gray-500
        ">
            <i class="pi pi-info-circle text-3xl mb-3 text-gray-400"></i>
            <p class="text-lg font-medium">
                There is no data available
            </p>
            <p class="text-sm mt-1">
                No information has been added yet.
            </p>
        </div>

        <!-- Footer -->
        <div class="text-right mt-6">
            <button class="px-5 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition"
                @click="showPreviewModal = false">
                Close
            </button>
        </div>

    </Dialog>

    <!--HISTORY  MODAL -->
    <Dialog v-model:visible="showHistoryModal" modal :header="modalTitle" :style="{ width: '50rem' }">

        <div class="max-h-96 overflow-y-auto space-y-4">
            <div v-if="historyData.length" class="space-y-4">
                <div v-for="(item, idx) in historyData" :key="idx" class="relative pl-8">
                    <!-- Timeline Dot -->
                    <div class="absolute left-0 top-1.5 w-3 h-3 bg-blue-600 rounded-full"></div>
                    <!-- Timeline Line -->
                    <div v-if="idx !== historyData.length - 1" class="absolute left-1.5 top-6 w-0.5 h-full bg-gray-300">
                    </div>

                    <!-- History Card -->
                    <div class="bg-white p-4 rounded-lg shadow hover:shadow-md transition">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500">{{ new Date(item.created_at).toLocaleString()
                            }}</span>

                            <span class="text-sm font-semibold text-blue-600">
                                {{ getStaffName(item.staff_id) }}
                            </span>
                        </div>

                        <div v-if="item.note && item.note.trim()" class="text-gray-800 whitespace-pre-line mb-2">
                            <strong>Note:</strong> {{ item.note }}
                        </div>

                        <div v-if="item.old_data && Object.keys(item.old_data).length"
                            class="bg-gray-50 p-2 rounded border-l-4 border-gray-300 text-sm text-gray-600">
                            <strong>Changed Fields:</strong>
                            <ul class="list-disc list-inside mt-1">
                                <li v-for="(value, key) in item.old_data" :key="key" class="flex gap-2">
                                    <span class="font-medium capitalize">
                                        {{ key.replace(/_/g, ' ') }}:
                                    </span>

                                    <!-- HTML content -->
                                    <span v-if="typeof value === 'string' && value.includes('<')"
                                        v-html="formatHistoryValue(key, value)"
                                        class="prose prose-sm max-w-none"></span>

                                    <!-- Normal text / formatted date -->
                                    <span v-else class="text-gray-700">
                                        {{ formatHistoryValue(key, value) }}
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-gray-500 py-8">
                No history available
            </div>
        </div>

        <div class="text-right mt-6">
            <button class="px-5 py-2 bg-blue-600 text-white font-medium rounded-md shadow hover:bg-blue-700 transition"
                @click="showHistoryModal = false">
                Close
            </button>
        </div>
    </Dialog>

    <!-- Add Note Modal -->
    <Dialog v-model:visible="showExtraNoteModal" header="Customer Notes & History" :style="{ width: '50rem' }">
        <div class="mb-4">
            <textarea v-model="newNote" class="w-full border rounded-lg p-3 focus:ring-2 focus:ring-blue-400" rows="4"
                placeholder="Write a new note here..."></textarea>
        </div>

        <div class="mb-4">
            <div class="flex justify-end gap-2">
                <button @click="saveNote"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition">Save</button>
                <button @click="showExtraNoteModal = false"
                    class="bg-gray-300 px-5 py-2 rounded-lg hover:bg-gray-400 transition">Close</button>
            </div>
        </div>

        <div class="mb-4">
            <h3 class="font-semibold text-gray-800 text-lg mb-2 border-b pb-1">Previous Notes</h3>
            <div v-if="customerHistory.length" class="space-y-3">

                <div v-for="(h, idx) in customerHistory" :key="h.id" class="relative p-4 rounded-xl
                            bg-white/40 backdrop-blur-md
                            border border-white/50
                            shadow-sm hover:shadow-md transition">

                    <!-- Header -->
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-500">
                            {{ new Date(h.created_at).toLocaleString() }}
                        </span>

                        <div class="flex items-center gap-2">
                            <!-- Edit button (only last 2 → already limited) -->
                            <button class="text-gray-500 hover:text-blue-600" @click="startEdit(h)">
                                <i class="pi pi-pencil"></i>
                            </button>
                        </div>
                    </div>

                    <!-- VIEW MODE -->
                    <div v-if="editingNoteId !== h.id" class="text-gray-800 whitespace-pre-line">
                        {{ h.note }}
                    </div>

                    <!-- EDIT MODE -->
                    <div v-else>
                        <textarea v-model="editingContent" rows="3"
                            class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-400"></textarea>

                        <div class="flex justify-end gap-2 mt-2">
                            <button class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
                                @click="updateNote">
                                Save
                            </button>
                            <button class="px-3 py-1 bg-gray-300 rounded hover:bg-gray-400"
                                @click="editingNoteId = null">
                                Cancel
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <div v-else class="text-center text-gray-500 py-6">
                No notes available
            </div>
        </div>
    </Dialog>

    <!-- MODALS -->
    <LeadSourceEntryModal v-model:visible="showLeadSourceModal" @created="handleLeadSourceCreated" />
    <ShopTypeEntryModal v-model:visible="showShopTypeModal" @created="handleShopTypeCreated" />
    <LocationEntryModal v-model:visible="showLocationModal" @created="handleLocationCreated" />
    <DesignationEntryModal v-model:visible="showDesignationModal" @created="handleDesignationCreated" />
    <InterestLevelEntryModal v-model:visible="showInterestLevelModal" @created="handleInterestLevelCreated" />
    <ServiceTypeEntryModal v-model:visible="showServiceTypeModal" @created="handleServiceTypeCreated" />
    <OfferEntryModal v-model:visible="showOfferModal" @created="handleOfferCreated" />
</template>

<style>
@import "vue-multiselect/dist/vue-multiselect.css";

/* Remove scrollbar */
.multiselect__content-wrapper {
    scrollbar-width: none;
    /* Firefox */
}

.multiselect__content-wrapper::-webkit-scrollbar {
    display: none;
    /* Chrome, Safari */
}

/* Make checkboxes clickable anywhere */
.p-checkbox {
    cursor: pointer;
}

.p-dropdown {
    width: 100%;
}

.fade-enter-active,
.fade-leave-active {
    transition: all 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
</style>
