import { Link } from 'react-router-dom'
import { LEGAL } from '../config/legal'

export default function Privacy() {
  return (
    <div className="page legal-page">
      <header className="page-header">
        <span className="eyebrow">Informativa</span>
        <h1>
          Privacy <em>policy</em>
        </h1>
        <p className="page-subtitle">
          Informativa sul trattamento dei dati personali ai sensi degli artt. 13 e 14 del
          Regolamento (UE) 2016/679 (GDPR). Ultimo aggiornamento: {LEGAL.privacyUpdatedAt}.
        </p>
      </header>

      <article className="card legal-content">
        <h2>1. Titolare del trattamento</h2>
        <p>
          Il titolare del trattamento è {LEGAL.owner}, che gestisce il negozio online F&amp;L Beauty (di
          seguito "F&amp;L Beauty"). Per qualsiasi domanda sui tuoi dati puoi scrivere a{' '}
          <a href={`mailto:${LEGAL.email}`}>{LEGAL.email}</a>.
        </p>

        <h2>2. Quali dati raccogliamo</h2>
        <ul>
          <li>
            <strong>Dati dell'account</strong>: nome e cognome, username, indirizzo email e password
            (salvata solo in forma cifrata, nessuno può leggerla, nemmeno noi), data e ora degli accessi
            al tuo account.
          </li>
          <li>
            <strong>Dati di spedizione</strong>: indirizzi, comune, CAP, provincia e numero di telefono.
          </li>
          <li>
            <strong>Dati degli ordini</strong>: prodotti acquistati, importi, codici sconto usati, stato
            dell'ordine e dati di tracciamento della spedizione.
          </li>
          <li>
            <strong>Preferenze sul sito</strong>: prodotti nel carrello e nei preferiti.
          </li>
          <li>
            <strong>Dati tecnici</strong>: indirizzo IP e orario delle richieste, usati per la sicurezza
            (ad esempio per bloccare chi prova a indovinare le password).
          </li>
          <li>
            <strong>Messaggi</strong> che ci invii via email o WhatsApp.
          </li>
        </ul>
        <p>Non raccogliamo dati di pagamento: il pagamento avviene fuori dal sito.</p>

        <h2>3. Perché li usiamo e su quale base giuridica</h2>
        <ul>
          <li>
            <strong>Gestire il tuo account e i tuoi ordini</strong> (registrazione, spedizione, assistenza,
            email sullo stato dell'ordine): è necessario per eseguire il contratto di acquisto (art. 6.1.b
            GDPR). Senza questi dati non possiamo spedirti i prodotti.
          </li>
          <li>
            <strong>Comunicazioni di servizio</strong> su account e ordini (ad esempio modifiche alle
            condizioni o problemi con una spedizione): esecuzione del contratto e nostro legittimo
            interesse (art. 6.1.b e 6.1.f GDPR).
          </li>
          <li>
            <strong>Obblighi di legge</strong>, ad esempio fiscali e contabili (art. 6.1.c GDPR).
          </li>
          <li>
            <strong>Sicurezza del sito</strong> e prevenzione di abusi: nostro legittimo interesse (art.
            6.1.f GDPR).
          </li>
          <li>
            <strong>Statistiche interne</strong> sull'uso degli account (quanti clienti si registrano, da
            quali città, quanto spesso accedono), per migliorare il negozio: nostro legittimo interesse
            (art. 6.1.f GDPR). Queste statistiche le vediamo solo noi e non vengono condivise.
          </li>
          <li>
            <strong>Email promozionali</strong> (offerte, sconti, novità): solo se ci dai il consenso (art.
            6.1.a GDPR). Il consenso è facoltativo e puoi revocarlo in qualsiasi momento dal tuo account o
            con il link presente in ogni email promozionale, senza conseguenze sui tuoi ordini.
          </li>
          <li>
            <strong>Statistiche di visita</strong> tramite Google Analytics, se attivo e solo con il tuo
            consenso: vedi la <Link to="/cookie">cookie policy</Link>.
          </li>
        </ul>

        <h2>4. A chi comunichiamo i dati</h2>
        <p>I tuoi dati non vengono venduti né ceduti a terzi per fini commerciali. Li trattano per nostro conto solo:</p>
        <ul>
          <li>il fornitore di hosting che ospita il sito e i dati, con server nell'Unione Europea;</li>
          <li>il fornitore del servizio di invio email;</li>
          <li>il corriere scelto per la spedizione (ad esempio Poste Italiane o SDA), per consegnarti il pacco;</li>
          <li>consulenti fiscali e contabili, quando serve per obblighi di legge;</li>
          <li>le autorità, se richiesto dalla legge.</li>
        </ul>
        <p>
          Se scegli di contattarci o di inviarci il riepilogo dell'ordine su WhatsApp, i dati che scrivi
          sono trattati anche da WhatsApp secondo la sua informativa.
        </p>

        <h2>5. Per quanto tempo li conserviamo</h2>
        <ul>
          <li>
            Dati dell'account: finché l'account resta attivo. Puoi eliminarlo in ogni momento dal tuo
            profilo ("Elimina account") o chiederci di farlo.
          </li>
          <li>Dati degli ordini e documenti fiscali: 10 anni, come previsto dalla legge.</li>
          <li>Consenso alle email promozionali: fino alla revoca.</li>
          <li>Storico degli accessi al tuo account: 12 mesi.</li>
          <li>Dati tecnici di sicurezza (IP e orari delle richieste): al massimo 14 giorni.</li>
        </ul>

        <h2>6. I tuoi diritti</h2>
        <p>In qualsiasi momento puoi chiederci di:</p>
        <ul>
          <li>accedere ai tuoi dati e riceverne una copia;</li>
          <li>correggerli o aggiornarli;</li>
          <li>cancellarli, salvo quelli che dobbiamo conservare per legge;</li>
          <li>limitarne il trattamento o opporti al trattamento basato sul legittimo interesse;</li>
          <li>riceverli in un formato leggibile per trasferirli a un altro servizio (portabilità);</li>
          <li>revocare il consenso alle email promozionali.</li>
        </ul>
        <p>
          Scrivi a <a href={`mailto:${LEGAL.email}`}>{LEGAL.email}</a>: ti rispondiamo entro 30 giorni. Se
          ritieni che i tuoi dati siano trattati in modo non corretto puoi presentare reclamo al Garante
          per la protezione dei dati personali (
          <a href="https://www.garanteprivacy.it" target="_blank" rel="noopener noreferrer">
            www.garanteprivacy.it
          </a>
          ).
        </p>

        <h2>7. Sicurezza</h2>
        <p>
          Proteggiamo i dati con misure tecniche adeguate: password cifrate, blocco automatico dopo troppi
          tentativi di accesso, accesso al pannello di gestione riservato al personale autorizzato e
          collegamento protetto (HTTPS).
        </p>

        <h2>8. Minori</h2>
        <p>
          Per registrarti devi avere almeno 14 anni: lo dichiari al momento della registrazione. Se
          scopriamo che un account appartiene a una persona più giovane, lo cancelliamo insieme ai suoi dati.
        </p>

        <h2>9. Modifiche</h2>
        <p>
          Possiamo aggiornare questa informativa: la data dell'ultima modifica è indicata in alto. In caso
          di cambiamenti importanti ti avviseremo via email.
        </p>
      </article>
    </div>
  )
}
