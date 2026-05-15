export interface SetupCity {
  id: number
  name: string
  councilor_limit?: number
}

export interface SetupPosition {
  id: number
  title: string
  max_votes: number
}

export interface SetupCandidate {
  id: number
  position_id: number
  name: string
  party_alias: string
  position_title?: string
}

export interface SetupPayload {
  city: SetupCity
  positions: SetupPosition[]
  candidates: SetupCandidate[]
  valid_ballots: string[]
}

export interface SetupStatus {
  city_id?: string
  city_name?: string
}

export interface BallotValidationResponse {
  status: 'valid' | 'used' | 'invalid'
  message?: string
}

export interface VotePayload {
  ballot_number: string
  choices: number[]
}

export interface LocalTallyRow {
  candidate_id: number
  candidate_name: string
  position_title: string
  party_alias: string
  vote_count: number
}

export interface ResultExportPayload {
  city_id: number
  results: Array<{ ballot_number: string; choices: number[] }>
}

export interface ResultStats {
  total_ballots: number
  used_ballots: number
  total_votes: number
}
