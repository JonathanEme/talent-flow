export interface Company {
  id: number;
  name: string;
  email?: string;
  phone?: string;
  website?: string;
}

export interface Skill {
  id: number;
  name: string;
  category: string;
  pivot?: {
    is_mandatory: boolean;
    min_years_experience: number;
    expected_level: string;
    years_experience?: number;
    level?: string;
  };
}

export interface Position {
  id: number;
  company_id: number;
  title: string;
  description: string;
  seniority: string;
  modality: 'remote' | 'hybrid' | 'on_site';
  location?: string;
  employment_type?: string;
  salary_min?: number;
  salary_max?: number;
  vacancies?: number;
  opened_at?: string;
  closes_at?: string;
  applications_count?: number;
  status: string;
  company?: Company;
}

export interface Candidate {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  linkedin_url?: string;
  portfolio_url?: string;
  cv_path?: string;
  location?: string;
  summary?: string;
  salary_expectation?: number;
}

export interface Application {
  id: number;
  position_id: number;
  candidate_id: number;
  stage: 'new' | 'screening' | 'technical_interview' | 'final_interview' | 'offer' | 'hired' | 'rejected';
  status: 'active' | 'hired' | 'rejected';
  applied_at: string;
  candidate: Candidate;
  position?: Position;
}

export interface Offer {
  id: number;
  status: string;
  currency: string;
  salary_offered: number | string;
  start_date: string;
  decline_reason?: string;
  application?: Application;
}

export interface InterviewFeedback {
  id: number;
  score: number;
  recommendation: string;
}

export interface Interview {
  id: number;
  type: string;
  status: string;
  scheduled_at: string;
  location_or_link?: string;
  application?: Application;
  feedbacks?: InterviewFeedback[];
}

export interface MatchingDetail {
  skill_id: number;
  skill_name: string;
  is_mandatory: boolean;
  required_years: number;
  candidate_years: number;
  is_met: boolean;
}

export interface MatchingFactor {
  key: string;
  label: string;
  score: number;
  weight: number;
  points: number;
}

export interface CandidateRecommendation {
  candidate_id: number;
  candidate_name: string;
  email: string;
  overall_score: number;
  meets_all_mandatory: boolean;
  mandatory_matched: number;
  mandatory_total: number;
  optional_matched: number;
  optional_total: number;
  details: MatchingDetail[];
  factors?: MatchingFactor[];
  already_applied: boolean;
  application_id: number | null;
  application_stage: string | null;
}

export interface DashboardKpis {
  open_positions: number;
  total_candidates: number;
  active_applications: number;
  upcoming_interviews: number;
  hired_applications: number;
  rejected_applications: number;
}

export interface DashboardData {
  kpis: DashboardKpis;
  stage_distribution: Record<string, number>;
  status_distribution: Record<string, number>;
  upcoming_interviews: Interview[];
  recent_positions: Position[];
}

export interface TimelineEvent {
  type: string;
  title: string;
  details: {
    from_stage?: string;
    to_stage?: string;
    comment?: string;
    actor?: string;
    score?: number;
    recommendation?: string;
    notes?: string;
    evaluator?: string;
  };
  timestamp: string;
}