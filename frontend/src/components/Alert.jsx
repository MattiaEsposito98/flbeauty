import { LuCircleAlert, LuCircleCheck, LuInfo } from 'react-icons/lu'

const ICONS = {
  success: LuCircleCheck,
  error: LuCircleAlert,
  info: LuInfo,
}

export default function Alert({ type = 'info', children }) {
  const Icon = ICONS[type]

  return (
    <div className={`alert alert-${type}`} role={type === 'error' ? 'alert' : 'status'}>
      <Icon className="alert-icon" aria-hidden="true" />
      <div className="alert-body">{children}</div>
    </div>
  )
}
