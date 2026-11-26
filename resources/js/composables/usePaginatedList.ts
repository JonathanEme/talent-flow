import { ref, toValue, type MaybeRefOrGetter, type Ref } from 'vue';
import { apiClient } from '../api/client';
import { errorMessage, unwrapPaginated, type PaginationLinks, type PaginationMeta } from '../api/pagination';

export function usePaginatedList<T>(
  endpoint: MaybeRefOrGetter<string>,
  params: MaybeRefOrGetter<Record<string, unknown>> = () => ({}),
) {
  const items = ref<T[]>([]) as Ref<T[]>;
  const meta = ref<PaginationMeta | null>(null);
  const links = ref<PaginationLinks | null>(null);
  const page = ref(1);
  const loading = ref(false);
  const error = ref<string | null>(null);

  const load = async () => {
    loading.value = true;
    error.value = null;

    try {
      const response = await apiClient.get(toValue(endpoint), {
        params: { ...toValue(params), page: page.value },
      });
      const payload = unwrapPaginated<T>(response as { data: { data: { data: T[]; links?: PaginationLinks; meta?: PaginationMeta } } });

      items.value = payload.data;
      meta.value = payload.meta ?? null;
      links.value = payload.links ?? null;
    } catch (caught) {
      error.value = errorMessage(caught, 'No se pudo cargar la información.');
      items.value = [];
      meta.value = null;
      links.value = null;
    } finally {
      loading.value = false;
    }
  };

  const goToPage = async (nextPage: number) => {
    page.value = nextPage;
    await load();
  };

  const reload = () => load();

  return { items, meta, links, page, loading, error, load, goToPage, reload };
}
