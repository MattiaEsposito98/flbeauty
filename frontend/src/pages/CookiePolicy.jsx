import { Link } from 'react-router-dom'
import { LEGAL } from '../config/legal'
import { analyticsAvailable, openCookiePreferences } from '../utils/cookieConsent'

const TECHNICAL_STORAGE = [
  { name: 'token', purpose: "Ti mantiene connesso al tuo account dopo l'accesso", duration: '30 giorni o fino all\'uscita' },
  { name: 'cart', purpose: 'Ricorda i prodotti nel carrello se non hai effettuato l\'accesso', duration: 'Fino allo svuotamento del carrello' },
  { name: 'cookie_consent', purpose: 'Ricorda la tua scelta sui cookie', duration: '6 mesi', onlyWithAnalytics: true },
]

export default function CookiePolicy() {
  const storage = TECHNICAL_STORAGE.filter((item) => !item.onlyWithAnalytics || analyticsAvailable)

  return (
    <div className="page legal-page">
      <header className="page-header">
        <span className="eyebrow">Informativa</span>
        <h1>
          Cookie <em>policy</em>
        </h1>
        <p className="page-subtitle">Ultimo aggiornamento: {LEGAL.cookieUpdatedAt}.</p>
      </header>

      <article className="card legal-content">
        <h2>Cosa sono i cookie</h2>
        <p>
          I cookie e gli strumenti simili (come la memoria locale del browser) sono piccoli dati che un
          sito salva sul tuo dispositivo. Alcuni sono <strong>tecnici</strong>, cioè necessari a far
          funzionare il sito, e non richiedono il tuo consenso. Altri servono per statistiche o
          pubblicità e possono essere usati <strong>solo se li accetti</strong>.
        </p>

        <h2>Strumenti tecnici che usiamo</h2>
        <p>
          F&amp;L Beauty usa la memoria locale del browser, non cookie di profilazione e nessun
          cookie pubblicitario:
        </p>
        <div className="legal-table-wrap">
          <table className="legal-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>A cosa serve</th>
                <th>Durata</th>
              </tr>
            </thead>
            <tbody>
              {storage.map((item) => (
                <tr key={item.name}>
                  <td>
                    <code>{item.name}</code>
                  </td>
                  <td>{item.purpose}</td>
                  <td>{item.duration}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <h2>Statistiche (Google Analytics)</h2>
        {analyticsAvailable ? (
          <>
            <p>
              Con il tuo consenso usiamo Google Analytics 4, fornito da Google Ireland Limited, per sapere
              in forma aggregata quante persone visitano il sito e quali pagine guardano. Google
              Analytics salva i cookie <code>_ga</code> e <code>_ga_*</code> (durata fino a 2 anni). Non
              usiamo questi dati per pubblicità né per identificarti. I dati possono essere trasferiti
              negli Stati Uniti, sulla base del Data Privacy Framework UE-USA.
            </p>
            <p>
              Se rifiuti, Google Analytics non viene caricato e il sito funziona normalmente.{' '}
              <button type="button" className="link-button" onClick={openCookiePreferences}>
                Cambia le tue preferenze cookie
              </button>
            </p>
          </>
        ) : (
          <p>
            Al momento non usiamo strumenti di statistica né altri cookie che richiedono il consenso, per
            questo non ti mostriamo alcun banner. Se in futuro li attiveremo, ti chiederemo prima il
            consenso e aggiorneremo questa pagina.
          </p>
        )}

        <h2>Come gestire i cookie dal browser</h2>
        <p>
          Puoi cancellare i dati salvati dal sito in qualsiasi momento dalle impostazioni del tuo browser
          (Chrome, Safari, Firefox, Edge), nella sezione privacy o dati dei siti. Cancellandoli dovrai
          accedere di nuovo al tuo account.
        </p>

        <h2>Titolare e contatti</h2>
        <p>
          Il titolare è {LEGAL.owner}. Per domande scrivi a{' '}
          <a href={`mailto:${LEGAL.email}`}>{LEGAL.email}</a>. Maggiori dettagli su come trattiamo i
          tuoi dati nella <Link to="/privacy">privacy policy</Link>.
        </p>
      </article>
    </div>
  )
}
