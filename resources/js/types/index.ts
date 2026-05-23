export interface User {
  id: number
  name: string
  email: string
  role: 'student' | 'trainer' | 'admin'
  level: 'beginner' | 'intermediate' | 'advanced' | null
  goal: 'fat_loss' | 'hypertrophy' | 'strength' | 'endurance' | 'mobility' | null
  equipment: string[] | null
  injuries: Injury[] | null
  avatar: string | null
  onboarding_completed_at: string | null
}

export interface Injury {
  zone: 'lumbar' | 'rodilla' | 'hombro' | 'muneca' | 'cadera'
  phase: 'aguda' | 'subaguda' | 'cronica' | 'retorno'
  notes: string
}

export interface Exercise {
  id: number
  name: string
  slug: string
  muscle_group: string
  movement_pattern: 'push' | 'pull' | 'hinge' | 'squat' | 'carry' | 'rotation' | 'core'
  level: 'beginner' | 'intermediate' | 'advanced'
  environment: 'gym' | 'home' | 'both'
  goal_tags: string[]
  description: string
  instructions: string[]
  common_errors: string[]
  thumbnail: string | null
  video_url: string | null
  met_value: number
  knowledge_key: string | null
}

export interface RoutineExercise {
  id: number
  exercise: Exercise
  sets: number
  reps: string
  duration_seconds: number | null
  rest_seconds: number
  rir: number | null
  order: number
}

export interface RoutineDay {
  id: number
  day_number: number
  name: string
  focus: string
  exercises: RoutineExercise[]
}

export interface Routine {
  id: number
  name: string
  generated_by_ai: boolean
  goal: string
  days_per_week: number
  is_active: boolean
  days: RoutineDay[]
}

export interface WorkoutSet {
  id: number
  set_number: number
  reps_done: number | null
  weight_kg: number | null
  rpe: number | null
  completed_at: string | null
}

export interface WorkoutLog {
  id: number
  date: string
  duration_minutes: number | null
  notes: string | null
  completed: boolean
  routine_day: RoutineDay | null
  sets: WorkoutSet[]
}

export interface ProgressEntry {
  id: number
  date: string
  weight_kg: number | null
  body_fat_pct: number | null
  notes: string | null
  photo_url: string | null
}

export interface ChatMessage {
  id: number
  role: 'user' | 'assistant'
  content: string
  tokens_used: number | null
  created_at: string
}

export interface PostureResult {
  exercise_key: string
  feedback: FeedbackResult[]
  session_at: string
}

export interface FeedbackResult {
  feedback: string
  severity: 'ok' | 'warning' | 'error'
  joint: string
}

export interface TrainerStudent {
  id: number
  trainer: User
  student: User
  status: 'pending' | 'active' | 'rejected'
  accepted_at: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}

declare module '@inertiajs/vue3' {
  interface PageProps {
    auth: {
      user: User | null
    }
    flash: {
      success?: string
      error?: string
    }
  }
}
