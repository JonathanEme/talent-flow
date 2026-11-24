import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '../views/DashboardView.vue';
import PositionsView from '../views/PositionsView.vue';
import PositionDetailView from '../views/PositionDetailView.vue';
import PositionCreateView from '../views/PositionCreateView.vue';
import PipelineKanbanView from '../views/PipelineKanbanView.vue';
import CandidateTimelineView from '../views/CandidateTimelineView.vue';
import CandidatesView from '../views/CandidatesView.vue';
import CandidateDetailView from '../views/CandidateDetailView.vue';
import CompaniesView from '../views/CompaniesView.vue';
import InterviewsView from '../views/InterviewsView.vue';
import OffersView from '../views/OffersView.vue';
import LoginView from '../views/LoginView.vue';
import { getToken } from '../api/token';

const routes = [
  { path: '/login', name: 'login', component: LoginView },
  { path: '/', name: 'dashboard', component: DashboardView },
  { path: '/positions', name: 'positions', component: PositionsView },
  { path: '/positions/create', name: 'position-create', component: PositionCreateView },
  { path: '/positions/:id', name: 'position-detail', component: PositionDetailView },
  { path: '/pipeline', name: 'pipeline', component: PipelineKanbanView },
  { path: '/candidates', name: 'candidates', component: CandidatesView },
  { path: '/candidates/:id', name: 'candidate-detail', component: CandidateDetailView },
  { path: '/companies', name: 'companies', component: CompaniesView },
  { path: '/interviews', name: 'interviews', component: InterviewsView },
  { path: '/offers', name: 'offers', component: OffersView },
  { path: '/applications/:id/timeline', name: 'application-timeline', component: CandidateTimelineView },
];

export const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to) => {
  if (to.path !== '/login' && !getToken()) {
    return { name: 'login' };
  }
  return true;
});
