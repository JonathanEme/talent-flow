<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { LogIn } from 'lucide-vue-next';
import { login } from '../api/auth';

const router = useRouter();
const email = ref('');
const password = ref('');
const error = ref<string | null>(null);
const loading = ref(false);

const submit = async () => {
  error.value = null;
  loading.value = true;
  try {
    await login(email.value, password.value);
    await router.push('/');
  } catch (err: any) {
    error.value = err.response?.data?.errors?.email?.[0]
      ?? err.response?.data?.message
      ?? 'No se pudo iniciar sesión.';
  } finally {
    loading.value = false;
  }
};
</script>

<template>
  <div class="min-h-[calc(100vh-8rem)] flex items-center justify-center">
    <form
      class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      @submit.prevent="submit"
    >
      <div class="mb-6 text-center">
        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-slate-900">
          <LogIn :size="20" />
        </div>
        <h1 class="text-xl font-bold text-slate-900">Ingresar a TalentFlow</h1>
        <p class="mt-1 text-sm text-slate-500">Usá tu cuenta de reclutador para continuar.</p>
      </div>

      <label class="mb-4 block">
        <span class="mb-1 block text-sm font-medium text-slate-700">Email</span>
        <input
          v-model="email"
          type="email"
          required
          autocomplete="email"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
        />
      </label>

      <label class="mb-5 block">
        <span class="mb-1 block text-sm font-medium text-slate-700">Contraseña</span>
        <input
          v-model="password"
          type="password"
          required
          autocomplete="current-password"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
        />
      </label>

      <p v-if="error" class="mb-4 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">
        {{ error }}
      </p>

      <button
        type="submit"
        :disabled="loading"
        class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-indigo-700 disabled:opacity-50"
      >
        {{ loading ? 'Ingresando...' : 'Ingresar' }}
      </button>
    </form>
  </div>
</template>
