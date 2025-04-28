<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3'; // Import usePage

// --- Modal State ---
const showSuccessModal = ref(false);
const successMessage = ref('');
const successUrl = ref('');
// -------------------


// Haal page props op voor gebruik in watch
const page = usePage();
const flashSuccess = computed(() => page.props.flash.success);
const flashUrl = computed(() => page.props.flash.newPreseedUrl);

// --- Watcher voor Success Flash Message ---
watch([flashSuccess, flashUrl], ([newSuccess, newUrl], [oldSuccess, oldUrl]) => {
  if (newSuccess && newSuccess !== oldSuccess) {
    successMessage.value = newSuccess;
    successUrl.value = newUrl || ''; // Zorg dat url een string is
    showSuccessModal.value = true;
  }
});
// --------------------------------------

// --- Standaard Waarden & Formulier Definitie ---
const form = useForm({
    // Identificatie
    config_name: '', // Naam voor deze configuratie (voor opslag/referentie)

    // Lokalisatie
    language: 'nl', // Standaard Nederlands
    country: 'NL',  // Standaard Nederland
    locale: 'nl_NL.UTF-8', // Standaard Nederlandse locale
    keyboard_layout: 'nl', // Standaard Nederlands toetsenbord

    // Netwerk
    hostname: 'debian',
    domain: 'localdomail.tld',
    network_method: 'dhcp', // 'dhcp' or 'static'
    static_ip: '',
    static_netmask: '',
    static_gateway: '',
    static_dns: '',
    network_device: 'eth0', // Of laat leeg voor auto-detect

    // Spiegel (Mirror)
    mirror_protocol: 'http',
    mirror_hostname: 'deb.debian.org',
    mirror_directory: '/debian',
    mirror_proxy: '', // Optioneel

    // Gebruikersinstellingen
    root_password: '',
    root_password_confirmation: '',
    disable_root_login: false, // Aanbevolen!
    create_user: true,
    username: 'debian', // Standaard Nederlandse gebruikersnaam
    user_fullname: 'Debian Gebruiker', // Standaard Nederlandse volledige naam
    user_password: 'installer',
    user_password_confirmation: 'installer',

    // Partitionering (Vereenvoudigd)
    partitioning_method: 'regular', // 'regular', 'lvm', 'crypto' (Guided - use entire disk)
    separate_home: false, // Alleen relevant voor 'regular'

    // Software Selectie
    tasks: ['standard', 'ssh-server'], // 'standard', 'desktop', 'gnome-desktop', 'kde-desktop', 'web-server', 'ssh-server', etc.
    additional_packages: '', // Spatie-gescheiden lijst

    // Tijdzone
    timezone: 'Europe/Amsterdam', // Standaard Nederlandse tijdzone

    // Bootloader
    grub_install_device: '/dev/sda', // Of 'all' of specifiek device

    // Late command (uitvoeren na installatie)
    late_command: '',

     // Security Hardening
     cis_compliant: false,
});

// --- Helper voor conditionele velden ---
const isStaticNetwork = ref(form.network_method === 'static');
const isCreateUserChecked = ref(form.create_user);

// --- Functie om het formulier te submitten ---
const submit = () => {
    // Reset modal state voor het geval er een oude melding was
    showSuccessModal.value = false;
    successMessage.value = '';
    successUrl.value = '';

    form.post(route('preseed.store'), {
        preserveScroll: true,
        // onSuccess wordt nu afgehandeld door de watcher
        onError: (errors) => {
            const firstErrorKey = Object.keys(errors)[0];
            if (firstErrorKey) {
                const errorElement = document.getElementById(firstErrorKey);
                if (errorElement) errorElement.focus();
            }
            console.error('Formulier fouten:', errors);
            // Optioneel: Toon error modal/alert hier ook
        },
    });
};

// --- Update conditionele vlaggen ---
watch(() => form.network_method, (newValue) => { isStaticNetwork.value = newValue === 'static'; });
watch(() => form.create_user, (newValue) => { isCreateUserChecked.value = newValue; });

// --- Watcher voor CIS compliance ---
watch(() => form.cis_compliant, (isCisChecked) => {
    if (isCisChecked) {
        form.disable_root_login = true;
        if (!form.additional_packages.includes('auditd')) {
             form.additional_packages = (form.additional_packages + ' auditd').trim();
        }
        if (!form.tasks.includes('ssh-server')) {
            form.tasks.push('ssh-server');
        }
        // Backend handelt CIS partitionering af
    }
    // Geen 'else' nodig, gebruiker kan 'disable_root_login' weer uitzetten als CIS wordt uitgevinkt
}, { immediate: false });


// --- Opties voor select/checkboxes ---
// Aangepast naar Nederlandse defaults waar logisch
const languageOptions = [ { value: 'nl', label: 'Nederlands' }, { value: 'en', label: 'English' }, { value: 'de', label: 'Deutsch' }];
const countryOptions = [ { value: 'NL', label: 'Netherlands' }, { value: 'US', label: 'United States' }, { value: 'DE', label: 'Germany' }, { value: 'BE', label: 'Belgium'} ];
const keyboardOptions = [ { value: 'nl', label: 'Dutch' }, { value: 'us', label: 'US English' }, { value: 'de', label: 'German' }, { value: 'be-latin1', label: 'Belgian'} ];
const partitioningOptions = [
    { value: 'regular', label: 'Begeleid - Gebruik volledige schijf' },
    { value: 'lvm', label: 'Begeleid - Gebruik volledige schijf en stel LVM in' },
    { value: 'crypto', label: 'Begeleid - Gebruik volledige schijf en stel versleutelde LVM in' },
];
const taskOptions = [
    { value: 'standard', label: 'Standaard systeemgereedschappen' },
    { value: 'web-server', label: 'Webserver' },
    { value: 'ssh-server', label: 'SSH-server' },
    // Voeg eventueel desktop taken toe indien gewenst
    // { value: 'desktop', label: 'Debian desktop environment' },
    // { value: 'gnome-desktop', label: 'GNOME' },
    // { value: 'kde-desktop', label: 'KDE Plasma' },
];

// Standaard CSS klassen voor formulier elementen
const inputClass = "mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm";
const labelClass = "block font-medium text-sm text-gray-700 dark:text-gray-300";
const errorClass = "mt-2 text-sm text-red-600 dark:text-red-400";
const checkboxClass = "rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 dark:bg-gray-900";
const buttonClass = "inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150";

// --- Refs voor preview ---
const previewContent = ref('');
const isPreviewLoading = ref(false);
const previewError = ref('');

// --- Functie om preview op te halen ---
async function fetchPreview() {
    isPreviewLoading.value = true;
    previewContent.value = '';
    previewError.value = '';

    try {
        const response = await axios.post(route('preseed.preview'), form.data());
        if (response.data.success) {
            previewContent.value = response.data.previewContent;
        } else {
             previewError.value = response.data.message || 'Onbekende fout bij genereren preview.';
        }
    } catch (error) {
        console.error("Preview Error:", error);
        if (error.response) {
            if (error.response.status === 422 && error.response.data.errors) {
                 const firstErrorKey = Object.keys(error.response.data.errors)[0];
                 const firstErrorMessage = error.response.data.errors[firstErrorKey][0];
                 previewError.value = `Validatiefout: ${firstErrorMessage} (en mogelijk meer). Controleer het formulier.`;
            } else {
                 previewError.value = `Fout bij ophalen preview: ${error.response.data.message || error.message}`;
            }
        } else {
            previewError.value = `Kon geen verbinding maken voor preview: ${error.message}`;
        }
    } finally {
        isPreviewLoading.value = false;
    }
}

</script>

<template>
    <Head title="Nieuwe Debian Preseed Configuratie" />

    <!-- Container voor de hele pagina-inhoud -->
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Hoofdinhoud container met padding en max breedte -->
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 py-6">

             <!-- Taal Keuze Knoppen zijn hier verwijderd -->

            <!-- WAARSCHUWINGSBANNER -->
            <div class="bg-red-100 dark:bg-red-900 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-md mb-6" role="alert">
                <p class="font-bold">Let op!</p>
                <p>Deze preseed generator is momenteel in ontwikkeling. De gegenereerde configuraties zijn mogelijk niet volledig of correct. Gebruik op eigen risico en test grondig vóór productiegebruik.</p>
            </div>
            <!-- ------------------------- -->

            <!-- Titel -->
            <h1 class="font-semibold text-2xl text-gray-800 dark:text-gray-200 leading-tight mb-6">
                Nieuwe Debian 12 Preseed Configuratie Generator
            </h1>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash && ($page.props.flash.success || $page.props.flash.error)" class="p-6"> <!-- Optioneel: p-6 hier voor algemene padding -->
                        <!-- Succes Bericht met Link -->
                        <div v-if="$page.props.flash.success" class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-900 dark:text-green-300" role="alert">
                            <p>{{ $page.props.flash.success }}</p>
                            <!-- Toon de link als de URL is meegestuurd -->
                            <p v-if="$page.props.flash.newPreseedUrl" class="mt-2">
                                Bekijk de nieuwe configuratie hier:
                                <a
                                    :href="$page.props.flash.newPreseedUrl"
                                    class="font-bold text-indigo-700 dark:text-indigo-300 hover:underline"
                                    target="_blank" rel="noopener noreferrer"
                                >
                                    {{ $page.props.flash.newPreseedUrl }}
                                </a>
                            </p>
                        </div>
                        <!-- Fout Bericht -->
                        <div v-if="$page.props.flash.error" class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-900 dark:text-red-300" role="alert">
                            {{ $page.props.flash.error }}
                        </div>
                    </div>

                    <!-- Sectie: Algemeen -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Algemene Configuratie</legend>
                        <div>
                            <label :class="labelClass" for="config_name">Naam Configuratie *</label>
                            <input :class="inputClass" id="config_name" type="text" v-model="form.config_name" required autofocus />
                            <div :class="errorClass" v-if="form.errors.config_name">{{ form.errors.config_name }}</div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Security Hardening -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Security Hardening</legend>
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input :class="checkboxClass" type="checkbox" id="cis_compliant" v-model="form.cis_compliant" />
                            </div>
                            <div class="ml-3 text-sm">
                                <label :class="labelClass" for="cis_compliant">Pas CIS Benchmark Hardening toe (Basis)</label>
                                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                    Probeert basis hardening toe te passen volgens CIS aanbevelingen via late_command en past partitionering aan. Dit garandeert geen volledige compliance en vereist mogelijk aanvullende stappen na installatie. Controleer de gegenereerde late_command en partitionering in de uiteindelijke preseed file.
                                </p>
                                <div :class="errorClass" v-if="form.errors.cis_compliant">{{ form.errors.cis_compliant }}</div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Lokalisatie -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Taal & Toetsenbord</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClass" for="language">Taal *</label>
                                <select :class="inputClass" id="language" v-model="form.language" required>
                                    <option v-for="option in languageOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <div :class="errorClass" v-if="form.errors.language">{{ form.errors.language }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="country">Land *</label>
                                <select :class="inputClass" id="country" v-model="form.country" required>
                                     <option v-for="option in countryOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <div :class="errorClass" v-if="form.errors.country">{{ form.errors.country }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="locale">Locale *</label>
                                <input :class="inputClass" id="locale" type="text" v-model="form.locale" required placeholder="e.g., nl_NL.UTF-8" />
                                <div :class="errorClass" v-if="form.errors.locale">{{ form.errors.locale }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="keyboard_layout">Toetsenbord Indeling *</label>
                                <select :class="inputClass" id="keyboard_layout" v-model="form.keyboard_layout" required>
                                     <option v-for="option in keyboardOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <div :class="errorClass" v-if="form.errors.keyboard_layout">{{ form.errors.keyboard_layout }}</div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Netwerk -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Netwerk Configuratie</legend>
                         <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label :class="labelClass" for="hostname">Hostname *</label>
                                <input :class="inputClass" id="hostname" type="text" v-model="form.hostname" required />
                                <div :class="errorClass" v-if="form.errors.hostname">{{ form.errors.hostname }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="domain">Domain</label>
                                <input :class="inputClass" id="domain" type="text" v-model="form.domain" />
                                <div :class="errorClass" v-if="form.errors.domain">{{ form.errors.domain }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="network_device">Netwerk Interface (leeg = auto)</label>
                                <input :class="inputClass" id="network_device" type="text" v-model="form.network_device" placeholder="e.g., eth0, enp3s0" />
                                <div :class="errorClass" v-if="form.errors.network_device">{{ form.errors.network_device }}</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <span :class="labelClass">Configuratie Methode *</span>
                            <div class="mt-2 space-x-4">
                                <label class="inline-flex items-center">
                                    <input type="radio" class="form-radio text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 dark:bg-gray-900 dark:border-gray-700" name="network_method" value="dhcp" v-model="form.network_method">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">DHCP (Automatisch)</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" class="form-radio text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 dark:focus:ring-offset-gray-800 dark:bg-gray-900 dark:border-gray-700" name="network_method" value="static" v-model="form.network_method">
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Statisch IP</span>
                                </label>
                            </div>
                        </div>

                        <!-- Statische IP Velden -->
                        <div v-if="isStaticNetwork" class="space-y-4 border-t dark:border-gray-600 pt-4 mt-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Voer statische netwerkdetails in:</p>
                            <div>
                                <label :class="labelClass" for="static_ip">IP Adres *</label>
                                <input :class="inputClass" id="static_ip" type="text" v-model="form.static_ip" :required="isStaticNetwork" />
                                <div :class="errorClass" v-if="form.errors.static_ip">{{ form.errors.static_ip }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="static_netmask">Netmask *</label>
                                <input :class="inputClass" id="static_netmask" type="text" v-model="form.static_netmask" :required="isStaticNetwork" />
                                <div :class="errorClass" v-if="form.errors.static_netmask">{{ form.errors.static_netmask }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="static_gateway">Gateway *</label>
                                <input :class="inputClass" id="static_gateway" type="text" v-model="form.static_gateway" :required="isStaticNetwork" />
                                <div :class="errorClass" v-if="form.errors.static_gateway">{{ form.errors.static_gateway }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="static_dns">DNS Servers (spatie-gescheiden) *</label>
                                <input :class="inputClass" id="static_dns" type="text" v-model="form.static_dns" :required="isStaticNetwork" />
                                <div :class="errorClass" v-if="form.errors.static_dns">{{ form.errors.static_dns }}</div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Mirror -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Software Bron (Mirror)</legend>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label :class="labelClass" for="mirror_protocol">Protocol *</label>
                                <select :class="inputClass" id="mirror_protocol" v-model="form.mirror_protocol" required>
                                    <option value="http">HTTP</option>
                                    <option value="https">HTTPS</option>
                                    <option value="ftp">FTP</option>
                                </select>
                                <div :class="errorClass" v-if="form.errors.mirror_protocol">{{ form.errors.mirror_protocol }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="mirror_hostname">Mirror Hostname *</label>
                                <input :class="inputClass" id="mirror_hostname" type="text" v-model="form.mirror_hostname" required />
                                <div :class="errorClass" v-if="form.errors.mirror_hostname">{{ form.errors.mirror_hostname }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="mirror_directory">Directory *</label>
                                <input :class="inputClass" id="mirror_directory" type="text" v-model="form.mirror_directory" required />
                                <div :class="errorClass" v-if="form.errors.mirror_directory">{{ form.errors.mirror_directory }}</div>
                            </div>
                        </div>
                         <div>
                            <label :class="labelClass" for="mirror_proxy">HTTP Proxy (optioneel)</label>
                            <input :class="inputClass" id="mirror_proxy" type="text" v-model="form.mirror_proxy" placeholder="http://proxy.example.com:8080" />
                            <div :class="errorClass" v-if="form.errors.mirror_proxy">{{ form.errors.mirror_proxy }}</div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Gebruikersaccounts -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Gebruikersaccounts</legend>

                        <div class="space-y-4">
                            <template v-if="!form.cis_compliant">
                                <div>
                                    <label :class="labelClass" for="root_password">Root Wachtwoord (leeg = account uitgeschakeld)</label>
                                    <input :class="inputClass" id="root_password" type="password" v-model="form.root_password" :disabled="form.disable_root_login" />
                                    <div :class="errorClass" v-if="form.errors.root_password">{{ form.errors.root_password }}</div>
                                </div>
                                <div>
                                    <label :class="labelClass" for="root_password_confirmation">Bevestig Root Wachtwoord</label>
                                    <input :class="inputClass" id="root_password_confirmation" type="password" v-model="form.root_password_confirmation" :disabled="form.disable_root_login"/>
                                </div>
                            </template>
                            <div v-else>
                                <div class="p-3 bg-blue-100 dark:bg-gray-700 border-l-4 border-blue-500 dark:border-blue-400 text-blue-800 dark:text-blue-200 rounded">
                                    <p class="font-medium">Root Login Uitgeschakeld (CIS)</p>
                                    <p class="text-sm mt-1">
                                        Conform CIS aanbevelingen wordt de root login standaard uitgeschakeld. Er wordt geen root wachtwoord ingesteld. Zorg ervoor dat u een standaard gebruiker aanmaakt met sudo rechten.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input :class="checkboxClass" type="checkbox" id="disable_root_login" v-model="form.disable_root_login" :disabled="form.cis_compliant"  />
                                <label :class="[labelClass, 'ml-2', form.cis_compliant ? 'text-gray-400 dark:text-gray-500' : '']" for="disable_root_login">Schakel root login uit (aanbevolen, vereist gebruiker met sudo)
                                    <span v-if="form.cis_compliant" class="text-xs italic">(Geforceerd door CIS selectie)</span>
                                </label>
                            </div>

                            <hr class="dark:border-gray-600 my-4">

                            <div class="flex items-center mb-4">
                                <input :class="checkboxClass" type="checkbox" id="create_user" v-model="form.create_user" />
                                <label :class="[labelClass, 'ml-2']" for="create_user">Maak een standaard gebruiker aan</label>
                            </div>

                            <div v-if="isCreateUserChecked" class="space-y-4 pl-6 border-l dark:border-gray-600">
                                 <p class="text-sm text-gray-600 dark:text-gray-400">Details voor de standaard gebruiker (krijgt sudo rechten indien root login uitgeschakeld is):</p>
                                <div>
                                    <label :class="labelClass" for="user_fullname">Volledige Naam Gebruiker</label>
                                    <input :class="inputClass" id="user_fullname" type="text" v-model="form.user_fullname" :required="isCreateUserChecked" />
                                    <div :class="errorClass" v-if="form.errors.user_fullname">{{ form.errors.user_fullname }}</div>
                                </div>
                                <div>
                                    <label :class="labelClass" for="username">Gebruikersnaam *</label>
                                    <input :class="inputClass" id="username" type="text" v-model="form.username" :required="isCreateUserChecked" />
                                    <div :class="errorClass" v-if="form.errors.username">{{ form.errors.username }}</div>
                                </div>
                                <div>
                                    <label :class="labelClass" for="user_password">Wachtwoord Gebruiker *</label>
                                    <input :class="inputClass" id="user_password" type="password" v-model="form.user_password" :required="isCreateUserChecked" />
                                    <div :class="errorClass" v-if="form.errors.user_password">{{ form.errors.user_password }}</div>
                                </div>
                                <div>
                                    <label :class="labelClass" for="user_password_confirmation">Bevestig Wachtwoord Gebruiker *</label>
                                    <input :class="inputClass" id="user_password_confirmation" type="password" v-model="form.user_password_confirmation" :required="isCreateUserChecked" />
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Partitionering -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Schijfindeling (Partitioning)</legend>
                        <template v-if="!form.cis_compliant">
                            <div>
                                <label :class="labelClass" for="partitioning_method">Partitionering Methode *</label>
                                <select :class="inputClass" id="partitioning_method" v-model="form.partitioning_method" required>
                                    <option v-for="option in partitioningOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Alle methodes gebruiken de volledige eerste beschikbare schijf.</p>
                                <div :class="errorClass" v-if="form.errors.partitioning_method">{{ form.errors.partitioning_method }}</div>
                            </div>
                            <div class="mt-4 flex items-center" v-if="form.partitioning_method === 'regular'">
                                <input :class="checkboxClass" type="checkbox" id="separate_home" v-model="form.separate_home" />
                                <label :class="[labelClass, 'ml-2']" for="separate_home">Maak een aparte /home partitie aan</label>
                                <div :class="errorClass" v-if="form.errors.separate_home">{{ form.errors.separate_home }}</div>
                            </div>
                        </template>
                        <div v-else>
                            <div class="p-3 bg-blue-100 dark:bg-gray-700 border-l-4 border-blue-500 dark:border-blue-400 text-blue-800 dark:text-blue-200 rounded">
                                <p class="font-medium">CIS Compliant Partitionering Geselecteerd</p>
                                <p class="text-sm mt-1">
                                    Een specifieke partitioneringsstrategie conform CIS aanbevelingen (bv. aparte partities voor /tmp, /var, /var/log, /var/log/audit, /home met 'nodev', 'nosuid', 'noexec' opties waar van toepassing) zal worden toegepast. De exacte implementatie gebeurt op de backend.
                                </p>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Software -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Software Selectie</legend>
                        <div class="mb-4">
                            <label :class="labelClass">Te installeren taken (Tasksel) *</label>
                            <div class="mt-2 space-y-2 max-h-60 overflow-y-auto border dark:border-gray-600 p-3 rounded">
                                <label v-for="task in taskOptions" :key="task.value" class="flex items-center">
                                    <input :class="checkboxClass" type="checkbox" :value="task.value" v-model="form.tasks" />
                                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ task.label }}</span>
                                </label>
                            </div>
                            <div :class="errorClass" v-if="form.errors.tasks">{{ form.errors.tasks }}</div>
                        </div>
                         <div>
                            <label :class="labelClass" for="additional_packages">Extra te installeren pakketten (spatie-gescheiden)</label>
                            <textarea :class="inputClass" id="additional_packages" v-model="form.additional_packages" rows="3"></textarea>
                            <div :class="errorClass" v-if="form.errors.additional_packages">{{ form.errors.additional_packages }}</div>
                        </div>
                    </fieldset>

                     <!-- Sectie: Tijdzone & Bootloader -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Tijdzone & Bootloader</legend>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClass" for="timezone">Tijdzone *</label>
                                <input :class="inputClass" id="timezone" type="text" v-model="form.timezone" required placeholder="e.g., Europe/Amsterdam" />
                                <div :class="errorClass" v-if="form.errors.timezone">{{ form.errors.timezone }}</div>
                            </div>
                            <div>
                                <label :class="labelClass" for="grub_install_device">Installeer GRUB op *</label>
                                <input :class="inputClass" id="grub_install_device" type="text" v-model="form.grub_install_device" required placeholder="/dev/sda or 'all'" />
                                <div :class="errorClass" v-if="form.errors.grub_install_device">{{ form.errors.grub_install_device }}</div>
                            </div>
                        </div>
                    </fieldset>

                    <!-- Sectie: Late Command -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Late Command</legend>
                         <div>
                            <label :class="labelClass" for="late_command">Commando's uit te voeren aan het einde van de installatie (in target systeem)</label>
                            <textarea :class="inputClass" id="late_command" v-model="form.late_command" rows="5" placeholder="apt update && apt upgrade -y&#10;adduser mijngebruiker sudo&#10;echo 'Installatie voltooid' > /root/installatie.log"></textarea>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Elk commando op een nieuwe regel. Wordt uitgevoerd als root.</p>
                            <div :class="errorClass" v-if="form.errors.late_command">{{ form.errors.late_command }}</div>
                        </div>
                    </fieldset>

                    <!-- Preview Sectie -->
                    <fieldset class="border dark:border-gray-600 p-4 rounded">
                        <legend class="text-lg font-medium text-gray-900 dark:text-gray-100 px-2">Preview</legend>

                        <div class="mb-4">
                            <button
                                type="button"
                                @click="fetchPreview"
                                :class="[buttonClass, 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600']"
                                :disabled="isPreviewLoading"
                            >
                                <span v-if="isPreviewLoading">Preview Laden...</span>
                                <span v-else>Genereer Preview</span>
                            </button>
                        </div>

                        <div v-if="previewError" class="mb-4 p-3 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                            {{ previewError }}
                        </div>

                        <div v-if="previewContent" class="mt-4">
                            <label :class="labelClass" for="preseed_preview">Gegenereerde Preseed (Preview)</label>
                            <textarea
                                id="preseed_preview"
                                :class="[inputClass, 'font-mono text-xs h-96']"
                                :value="previewContent"
                                readonly
                            ></textarea>
                        </div>
                         <div v-else-if="isPreviewLoading" class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            Preview wordt gegenereerd...
                        </div>
                    </fieldset>

                    <!-- Submit Knop -->
                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" :class="[buttonClass, { 'opacity-25': form.processing }]" :disabled="form.processing">
                            Genereer & Sla Preseed Configuratie Op
                        </button>
                    </div>
                </form>
            </div>

            <!-- Success Modal -->
            <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="showSuccessModal = false"></div>

                <!-- Modal Content -->
                <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6">
                    <!-- Titel (Optioneel) -->
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-2">
                        Succes!
                    </h3>
                    <!-- Bericht -->
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        <p>{{ successMessage }}</p>
                        <!-- Link -->
                        <p v-if="successUrl" class="mt-3">
                            Bekijk de nieuwe configuratie hier:
                            <a
                                :href="successUrl"
                                class="font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {{ successUrl }}
                            </a>
                        </p>
                    </div>

                    <!-- Sluit Knop -->
                    <div class="mt-5 sm:mt-6">
                        <button
                            type="button"
                            @click="showSuccessModal = false"
                            class="inline-flex justify-center w-full rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm dark:bg-indigo-500 dark:hover:bg-indigo-600 dark:focus:ring-offset-gray-800"
                        >
                            Sluiten
                        </button>
                    </div>
                </div>
            </div>
            <!-- Einde Success Modal -->


            
            <!-- Link naar de show pagina -->
             <div class="mt-6 text-center">
                 <p class="text-sm text-gray-600 dark:text-gray-400">Bekijk opgeslagen configuraties (voorbeeld):</p>
                 <a href="/preseed/mijn-test-config/"
                     class="text-indigo-600 dark:text-indigo-400 hover:underline"
                     target="_blank"
                     rel="noopener noreferrer"
                 >
                     /preseed/mijn-test-config
                 </a>
             </div>

        </div> <!-- Einde hoofdinhoud container -->
    </div> <!-- Einde container voor hele pagina -->
</template>
