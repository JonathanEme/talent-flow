export interface Company {
  id: number;
  name: string;
  email?: string;
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
  salary_min?: number;
  salary_max?: number;
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