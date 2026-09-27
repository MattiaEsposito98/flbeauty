export default function EmptyState({ icon: Icon, title, children, action }) {
  return (
    <div className="empty-state">
      <span className="icon-circle icon-circle-lg" aria-hidden="true">
        <Icon />
      </span>
      <h3>{title}</h3>
      {children && <p>{children}</p>}
      {action}
    </div>
  )
}
