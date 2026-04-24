import { useState, useRef, useEffect } from 'react'
import { Hammer, Zap, Palette, Wrench, Download, Coffee, Copy, Check, X } from 'lucide-react'
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'

import './App.css'

function App() {
  // States
  const [status, setStatus] = useState('demo') // 'demo' or 'profissa'
  const [daysRemaining, setDaysRemaining] = useState(30)
  const [restorationsUsed, setRestorationsUsed] = useState(0)
  const [token, setToken] = useState(localStorage.getItem('profisstoken') || '')
  const [showTokenCopy, setShowTokenCopy] = useState(false)
  const [tokenCopied, setTokenCopied] = useState(false)
  const [showPaymentModal, setShowPaymentModal] = useState(false)
  
  // Form states
  const [selectedLogo, setSelectedLogo] = useState('hammer')
  const [companyName, setCompanyName] = useState('Sua Empresa')
  const [budgetNumber, setBudgetNumber] = useState(localStorage.getItem('budgetNumber') || '1001')
  const [items, setItems] = useState([
    { description: 'Serviço padrão', quantity: 1, value: 100 }
  ])
  
  const quotePreviewRef = useRef(null)
  
  // Logos disponíveis
  const logos = {
    hammer: { name: 'Martelo', Icon: Hammer },
    zap: { name: 'Raio', Icon: Zap },
    palette: { name: 'Pincel', Icon: Palette },
    wrench: { name: 'Chave de Fenda', Icon: Wrench }
  }
  
  // Gerar token na primeira vez
  useEffect(() => {
    if (!token) {
      const newToken = 'PF' + Date.now() + Math.random().toString(36).substr(2, 9).toUpperCase()
      setToken(newToken)
      localStorage.setItem('profisstoken', newToken)
      setShowTokenCopy(true)
    }
  }, [])
  
  // Adicionar item ao orçamento
  const addItem = () => {
    setItems([...items, { description: '', quantity: 1, value: 0 }])
  }
  
  // Atualizar item
  const updateItem = (index, field, value) => {
    const newItems = [...items]
    newItems[index] = { ...newItems[index], [field]: value }
    setItems(newItems)
  }
  
  // Remover item
  const removeItem = (index) => {
    setItems(items.filter((_, i) => i !== index))
  }
  
  // Calcular total
  const calculateTotal = () => {
    return items.reduce((total, item) => {
      const qty = parseFloat(item.quantity) || 0
      const val = parseFloat(item.value) || 0
      return total + (qty * val)
    }, 0)
  }
  
  // Copiar token
  const copyToken = () => {
    navigator.clipboard.writeText(token)
    setTokenCopied(true)
    setTimeout(() => setTokenCopied(false), 2000)
  }
  
  // Gerar PNG/JPG
  const exportImage = async (format = 'png') => {
    const element = quotePreviewRef.current
    const canvas = await html2canvas(element, { backgroundColor: '#fff' })
    const link = document.createElement('a')
    link.href = canvas.toDataURL(`image/${format}`)
    link.download = `orcamento-${budgetNumber}.${format}`
    link.click()
  }
  
  // Gerar PDF
  const exportPDF = async () => {
    const element = quotePreviewRef.current
    const canvas = await html2canvas(element, { backgroundColor: '#fff' })
    const imgData = canvas.toDataURL('image/png')
    const pdf = new jsPDF()
    const imgWidth = 210
    const imgHeight = (canvas.height * imgWidth) / canvas.width
    pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight)
    pdf.save(`orcamento-${budgetNumber}.pdf`)
  }
  
  const CurrentLogo = logos[selectedLogo].Icon
  const total = calculateTotal()
  
  return (
    <div className="app-container">
      {/* HEADER */}
      <header className="app-header">
        <div className="header-content">
          <div className="logo-section">
            <div className="app-icon">
              <Hammer size={32} />
            </div>
            <div>
              <h1 className="app-title">Profissapp</h1>
              <p className="app-subtitle">Gerador de orçamentos</p>
            </div>
          </div>
          
          <div className="status-section">
            <div className="status-item">
              <div className={`status-dot ${status}`}></div>
              <span className="status-text">{status === 'demo' ? 'Demo' : 'Profissa'}</span>
              {status === 'profissa' && (
                <span className="days-remaining">{daysRemaining} dias</span>
              )}
              {status === 'profissa' && restorationsUsed > 0 && (
                <span className="restorations">Restaurações {restorationsUsed}/3</span>
              )}
            </div>
          </div>
        </div>
      </header>

      {/* MAIN CONTENT */}
      <main className="main-content">
        {showTokenCopy && (
          <div className="token-modal">
            <div className="token-card">
              <h2>Seu Token</h2>
              <p className="token-description">Guarde este código para restaurar seu Profissa em outro dispositivo (até 3 vezes)</p>
              <div className="token-display">
                <code>{token}</code>
                <button className="copy-btn" onClick={copyToken}>
                  {tokenCopied ? <Check size={20} /> : <Copy size={20} />}
                </button>
              </div>
              <button className="close-token-btn" onClick={() => setShowTokenCopy(false)}>
                Continuar
              </button>
            </div>
          </div>
        )}

        <div className="content-wrapper">
          {/* FORMULÁRIO */}
          <section className="form-section">
            <div className="form-container">
              <h2>Dados da Empresa</h2>
              
              <div className="form-group">
                <label>Escolha seu Logo</label>
                <div className="logo-selector">
                  {Object.entries(logos).map(([key, { name, Icon }]) => (
                    <button
                      key={key}
                      className={`logo-btn ${selectedLogo === key ? 'active' : ''}`}
                      onClick={() => setSelectedLogo(key)}
                      title={name}
                    >
                      <Icon size={28} />
                      <span>{name}</span>
                    </button>
                  ))}
                </div>
              </div>
              
              <div className="form-group">
                <label htmlFor="company-name">Nome da Empresa</label>
                <input
                  id="company-name"
                  type="text"
                  value={companyName}
                  onChange={(e) => setCompanyName(e.target.value)}
                  placeholder="Digite o nome fantasia"
                  maxLength={50}
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="budget-number">Nº Orçamento</label>
                <input
                  id="budget-number"
                  type="number"
                  value={budgetNumber}
                  onChange={(e) => setBudgetNumber(e.target.value)}
                />
              </div>
            </div>

            <div className="form-container">
              <h2>Itens do Orçamento</h2>
              
              <div className="items-list">
                {items.map((item, index) => (
                  <div key={index} className="item-row">
                    <input
                      type="text"
                      className="item-description"
                      placeholder="Descrição do serviço"
                      value={item.description}
                      onChange={(e) => updateItem(index, 'description', e.target.value)}
                    />
                    <input
                      type="number"
                      className="item-quantity"
                      placeholder="Qtd"
                      value={item.quantity}
                      onChange={(e) => updateItem(index, 'quantity', e.target.value)}
                      min="0"
                      step="0.01"
                    />
                    <input
                      type="number"
                      className="item-value"
                      placeholder="Valor"
                      value={item.value}
                      onChange={(e) => updateItem(index, 'value', e.target.value)}
                      min="0"
                      step="0.01"
                    />
                    <button
                      className="remove-btn"
                      onClick={() => removeItem(index)}
                      title="Remover item"
                    >
                      <X size={18} />
                    </button>
                  </div>
                ))}
              </div>
              
              <button className="add-item-btn" onClick={addItem}>
                + Adicionar Item
              </button>
            </div>
          </section>

          {/* PREVIEW */}
          <section className="preview-section">
            <div className="preview-container">
              <h2>Visualização</h2>
              
              <div className="quote-preview" ref={quotePreviewRef}>
                {status === 'demo' && (
                  <div className="demo-banner">DEMONSTRAÇÃO</div>
                )}
                
                <div className="quote-header">
                  <div className="quote-logo-large">
                    <CurrentLogo size={60} />
                  </div>
                  <div className="quote-company-info">
                    <h3>{companyName}</h3>
                    <p>Orçamento Nº {budgetNumber}</p>
                  </div>
                </div>

                <table className="quote-table">
                  <thead>
                    <tr>
                      <th>Descrição</th>
                      <th>Qtd</th>
                      <th>Valor Unit.</th>
                      <th>Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((item, index) => (
                      <tr key={index}>
                        <td className="desc-cell">{item.description || '---'}</td>
                        <td className="center">{item.quantity || 0}</td>
                        <td className="right">R$ {(parseFloat(item.value) || 0).toFixed(2).replace('.', ',')}</td>
                        <td className="right bold">R$ {((parseFloat(item.quantity) || 0) * (parseFloat(item.value) || 0)).toFixed(2).replace('.', ',')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                <div className="quote-total">
                  <span>VALOR TOTAL:</span>
                  <span className="total-value">R$ {total.toFixed(2).replace('.', ',')}</span>
                </div>

                <div className="quote-footer">
                  <p>Orçamento válido por 7 dias</p>
                  <p>{new Date().toLocaleDateString('pt-BR')}</p>
                </div>
              </div>

              {/* BOTÕES DE AÇÃO */}
              <div className="action-buttons">
                <button className="export-btn" onClick={() => exportImage('png')}>
                  <Download size={18} /> PNG
                </button>
                <button className="export-btn" onClick={() => exportImage('jpg')}>
                  <Download size={18} /> JPG
                </button>
                <button className="export-btn pdf" onClick={exportPDF}>
                  <Download size={18} /> PDF
                </button>
              </div>

              {status === 'demo' && (
                <button className="payment-btn" onClick={() => setShowPaymentModal(true)}>
                  <Coffee size={18} /> Um cafezinho para remover Demo
                </button>
              )}
            </div>
          </section>
        </div>
      </main>

      {/* PAYMENT MODAL */}
      {showPaymentModal && (
        <div className="modal-overlay" onClick={() => setShowPaymentModal(false)}>
          <div className="modal-content" onClick={(e) => e.stopPropagation()}>
            <button className="modal-close" onClick={() => setShowPaymentModal(false)}>
              <X size={24} />
            </button>
            
            <h2>Colabore Conosco</h2>
            <p>Remova a tarja de demonstração por apenas um cafezinho!</p>
            
            <div className="qr-code-container">
              <QRCode value={token} size={256} level="H" includeMargin={true} />
            </div>
            
            <p className="payment-info">
              Escaneie o código QR com seu celular para realizar o pagamento
            </p>
            
            <p className="payment-token">
              Token: <strong>{token}</strong>
            </p>
            
            <div className="payment-status">
              <p>Aguardando confirmação de pagamento...</p>
              <div className="spinner"></div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default App
