<script setup>
import { Head, useForm } from '@inertiajs/vue3';

// Definieer de formuliervelden met useForm
const form = useForm({
  veldnaam1: '', // Moet overeenkomen met je validatie/controller
  veldnaam2: null,
  // Voeg hier andere velden toe die je nodig hebt
});

// Functie om het formulier te submitten
const submit = () => {
  form.post(route('preseed.store'), { // Gebruik de route naam
    // Optioneel: acties na succes of bij fouten
    // onSuccess: () => form.reset(),
    // onError: () => console.error('Fout bij opslaan'),
  });
};
</script>

<template>
  <Head title="Nieuwe Preseed" />

  <AuthenticatedLayout> <!-- Of je gekozen layout -->
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Nieuwe Preseed Aanmaken
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">

            <!-- Toon eventuele succesmeldingen -->
            <div v-if="$page.props.flash.success" class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
              {{ $page.props.flash.success }}
            </div>

            <!-- Het Formulier -->
            <form @submit.prevent="submit">
              <!-- Veld 1 -->
              <div class="mb-4">
                <label for="veldnaam1" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Veld 1</label>
                <input
                  id="veldnaam1"
                  v-model="form.veldnaam1"
                  type="text"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                  required
                />
                <!-- Toon validatiefout voor dit veld -->
                <div v-if="form.errors.veldnaam1" class="mt-2 text-sm text-red-600 dark:text-red-400">
                  {{ form.errors.veldnaam1 }}
                </div>
              </div>

              <!-- Veld 2 -->
              <div class="mb-4">
                <label for="veldnaam2" class="block font-medium text-sm text-gray-700 dark:text-gray-300">Veld 2 (Optioneel)</label>
                <input
                  id="veldnaam2"
                  v-model="form.veldnaam2"
                  type="number"
                  class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                />
                 <div v-if="form.errors.veldnaam2" class="mt-2 text-sm text-red-600 dark:text-red-400">
                  {{ form.errors.veldnaam2 }}
                </div>
              </div>

              <!-- Voeg hier meer formuliervelden toe -->

              <!-- Submit Knop -->
              <div class="flex items-center justify-end mt-4">
                <button
                  type="submit"
                  :class="{ 'opacity-25': form.processing }"
                  :disabled="form.processing"
                  class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                  Opslaan
                </button>
              </div>
            </form>

          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
