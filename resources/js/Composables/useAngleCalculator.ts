// Cálculo de ángulos articulares a partir de landmarks de MediaPipe Pose
// Consumido por usePostureFeedback.ts

export interface Landmark {
  x: number
  y: number
  z: number
  visibility?: number
}

// Índices de landmarks de MediaPipe Pose (33 puntos)
export const POSE_LANDMARKS = {
  NOSE: 0,
  LEFT_SHOULDER: 11,
  RIGHT_SHOULDER: 12,
  LEFT_ELBOW: 13,
  RIGHT_ELBOW: 14,
  LEFT_WRIST: 15,
  RIGHT_WRIST: 16,
  LEFT_HIP: 23,
  RIGHT_HIP: 24,
  LEFT_KNEE: 25,
  RIGHT_KNEE: 26,
  LEFT_ANKLE: 27,
  RIGHT_ANKLE: 28,
} as const

export function useAngleCalculator() {
  /**
   * Ángulo (en grados) en el vértice B formado por los puntos A-B-C.
   */
  function angleBetween(a: Landmark, b: Landmark, c: Landmark): number {
    const radians =
      Math.atan2(c.y - b.y, c.x - b.x) - Math.atan2(a.y - b.y, a.x - b.x)
    let angle = Math.abs((radians * 180) / Math.PI)
    if (angle > 180) angle = 360 - angle
    return Math.round(angle)
  }

  /** Ángulo de la rodilla: cadera–rodilla–tobillo */
  function kneeAngle(lm: Landmark[], side: 'left' | 'right'): number {
    const hip   = side === 'left' ? lm[POSE_LANDMARKS.LEFT_HIP]   : lm[POSE_LANDMARKS.RIGHT_HIP]
    const knee  = side === 'left' ? lm[POSE_LANDMARKS.LEFT_KNEE]  : lm[POSE_LANDMARKS.RIGHT_KNEE]
    const ankle = side === 'left' ? lm[POSE_LANDMARKS.LEFT_ANKLE] : lm[POSE_LANDMARKS.RIGHT_ANKLE]
    return angleBetween(hip, knee, ankle)
  }

  /** Ángulo del codo: hombro–codo–muñeca */
  function elbowAngle(lm: Landmark[], side: 'left' | 'right'): number {
    const shoulder = side === 'left' ? lm[POSE_LANDMARKS.LEFT_SHOULDER] : lm[POSE_LANDMARKS.RIGHT_SHOULDER]
    const elbow    = side === 'left' ? lm[POSE_LANDMARKS.LEFT_ELBOW]    : lm[POSE_LANDMARKS.RIGHT_ELBOW]
    const wrist    = side === 'left' ? lm[POSE_LANDMARKS.LEFT_WRIST]    : lm[POSE_LANDMARKS.RIGHT_WRIST]
    return angleBetween(shoulder, elbow, wrist)
  }

  /** Ángulo de la cadera: hombro–cadera–rodilla */
  function hipAngle(lm: Landmark[], side: 'left' | 'right'): number {
    const shoulder = side === 'left' ? lm[POSE_LANDMARKS.LEFT_SHOULDER] : lm[POSE_LANDMARKS.RIGHT_SHOULDER]
    const hip      = side === 'left' ? lm[POSE_LANDMARKS.LEFT_HIP]      : lm[POSE_LANDMARKS.RIGHT_HIP]
    const knee     = side === 'left' ? lm[POSE_LANDMARKS.LEFT_KNEE]     : lm[POSE_LANDMARKS.RIGHT_KNEE]
    return angleBetween(shoulder, hip, knee)
  }

  /**
   * Inclinación del torso respecto a la vertical (0° = totalmente erguido).
   * Usa el punto medio de hombros y caderas.
   */
  function backTilt(lm: Landmark[]): number {
    const midShoulder = {
      x: (lm[POSE_LANDMARKS.LEFT_SHOULDER].x + lm[POSE_LANDMARKS.RIGHT_SHOULDER].x) / 2,
      y: (lm[POSE_LANDMARKS.LEFT_SHOULDER].y + lm[POSE_LANDMARKS.RIGHT_SHOULDER].y) / 2,
      z: 0,
    }
    const midHip = {
      x: (lm[POSE_LANDMARKS.LEFT_HIP].x + lm[POSE_LANDMARKS.RIGHT_HIP].x) / 2,
      y: (lm[POSE_LANDMARKS.LEFT_HIP].y + lm[POSE_LANDMARKS.RIGHT_HIP].y) / 2,
      z: 0,
    }
    const dx = midShoulder.x - midHip.x
    const dy = midShoulder.y - midHip.y
    const radians = Math.atan2(Math.abs(dx), Math.abs(dy))
    return Math.round((radians * 180) / Math.PI)
  }

  /**
   * Valgo de rodilla: desviación horizontal de la rodilla respecto a la línea
   * cadera-tobillo. Aproximación en grados (0 = alineada).
   */
  function kneeValgus(lm: Landmark[], side: 'left' | 'right'): number {
    const hip   = side === 'left' ? lm[POSE_LANDMARKS.LEFT_HIP]   : lm[POSE_LANDMARKS.RIGHT_HIP]
    const knee  = side === 'left' ? lm[POSE_LANDMARKS.LEFT_KNEE]  : lm[POSE_LANDMARKS.RIGHT_KNEE]
    const ankle = side === 'left' ? lm[POSE_LANDMARKS.LEFT_ANKLE] : lm[POSE_LANDMARKS.RIGHT_ANKLE]

    // Posición horizontal esperada de la rodilla (interpolación lineal cadera→tobillo)
    const ratio = (knee.y - hip.y) / (ankle.y - hip.y || 1)
    const expectedX = hip.x + ratio * (ankle.x - hip.x)
    const deviation = Math.abs(knee.x - expectedX)

    // Convertir desviación normalizada a un ángulo aproximado
    return Math.round(deviation * 180)
  }

  /** Selecciona el lado más visible (mayor visibilidad media). */
  function bestSide(lm: Landmark[]): 'left' | 'right' {
    const leftVis =
      (lm[POSE_LANDMARKS.LEFT_HIP].visibility ?? 0) +
      (lm[POSE_LANDMARKS.LEFT_KNEE].visibility ?? 0) +
      (lm[POSE_LANDMARKS.LEFT_SHOULDER].visibility ?? 0)
    const rightVis =
      (lm[POSE_LANDMARKS.RIGHT_HIP].visibility ?? 0) +
      (lm[POSE_LANDMARKS.RIGHT_KNEE].visibility ?? 0) +
      (lm[POSE_LANDMARKS.RIGHT_SHOULDER].visibility ?? 0)
    return leftVis >= rightVis ? 'left' : 'right'
  }

  return {
    angleBetween,
    kneeAngle,
    elbowAngle,
    hipAngle,
    backTilt,
    kneeValgus,
    bestSide,
  }
}
