import { useState, useRef, useEffect } from 'react'
import { 
  Hammer, Zap, Palette, Wrench, Download, Coffee, Copy, Check, X, 
  Pointer, Briefcase, Cpu, Globe, Home, Settings, Truck, Camera, Phone,
  Scissors, PenTool, HardHat, Car, ShoppingCart, Heart, Star, 
  Cloud, Anchor, Bell, Key, Smartphone
} from 'lucide-react'
import html2canvas from 'html2canvas'
import jsPDF from 'jspdf'
import { QRCodeCanvas } from 'qrcode.react'   // 👈 ADICIONE ESTA LINHA

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
  const [isIconPickerOpen, setIsIconPickerOpen] = useState(false)
  
  // Form states
  const [selectedLogo, setSelectedLogo] = useState('Hammer')
  const [companyName, setCompanyName] = useState('')
  const [phone, setPhone] = useState('')
  const [budgetNumber, setBudgetNumber] = useState(localStorage.getItem('budgetNumber') || '1001')
  const [observations, setObservations] = useState('Cond de pgto: 30% à vista.\nRestante na entrega do serviço.\nEstimativa de entrega: 3 dias após pedido.\nOrçamento válido por 7 dias')
  const [items, setItems] = useState([
    { description: '', value: 100 }
  ])
  
  const quotePreviewRef = useRef(null)
  
  // Logos disponíveis
  const availableIcons = {
    Hammer, Zap, Palette, Wrench, Briefcase, Cpu, Globe, Home, 
    Settings, Truck, Camera, Scissors, PenTool, HardHat, Car, 
    ShoppingCart, Heart, Star, Cloud, Anchor, Bell, Key, Smartphone
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
  
  // Efeito para fechar o modal automaticamente quando o status mudar para 'profissa'
  useEffect(() => {
    if (status === 'profissa' && showPaymentModal) {
      // Adicionamos um pequeno delay de 2 segundos para o usuário ver 
      // uma mensagem de sucesso ou o status mudando antes de fechar
      const timer = setTimeout(() => {
        setShowPaymentModal(false);
        // Opcional: Feedback visual de sucesso
        console.log("Pagamento confirmado! Modal fechado automaticamente.");
      }, 2000);

      return () => clearTimeout(timer);
    }
  }, [status, showPaymentModal]);

  // Função para aplicar máscara de telefone (ex: (11) 99999-9999)
  const handlePhoneChange = (e) => {
    let value = e.target.value.replace(/\D/g, "");
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 10) {
      value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, "($1) $2-$3");
    } else if (value.length > 6) {
      value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, "($1) $2-$3");
    } else if (value.length > 2) {
      value = value.replace(/^(\d{2})(\d{0,5}).*/, "($1) $2");
    }
    setPhone(value);
  };

  // Adicionar item ao orçamento
  const addItem = () => {
    setItems([...items, { description: '', value: 0 }])
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
      const val = parseFloat(item.value) || 0
      return total + val
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
  
  const CurrentLogo = availableIcons[selectedLogo] || Hammer
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
              <div className={`status-dot ${status === 'demo' ? 'demo' : 'off'}`}></div>
              <span className="status-text">Demo</span>
            </div>
            <div className="status-item">
              <div className={`status-dot ${status === 'profissa' ? 'profissa' : 'off'}`}></div>
              <span className="status-text">Profissa</span>
              <div className="status-details">
                {status === 'profissa' && (
                  <span className="days-remaining">{daysRemaining} dias</span>
                )}
                {status === 'profissa' && restorationsUsed > 0 && (
                  <span className="restorations">Restaurações {restorationsUsed}/3</span>
                )}
              </div>
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
                <div className="logo-selection-inline">
                  <span>Escolha seu Logo</span>
                  <div className="pointer-icon-container">
                    <Pointer size={18} className="pointer-icon" />
                  </div>
                  <button 
                    className="font-awesome-trigger"
                    onClick={() => setIsIconPickerOpen(!isIconPickerOpen)}
                  >
                    Font Awesome
                  </button>
                </div>

                {isIconPickerOpen && (
                  <div className="icon-picker-grid">
                    {Object.entries(availableIcons).map(([name, Icon]) => (
                      <button
                        key={name}
                        className={`icon-option ${selectedLogo === name ? 'active' : ''}`}
                        onClick={() => {
                          setSelectedLogo(name)
                          setIsIconPickerOpen(false)
                        }}
                        title={name}
                      >
                        <Icon size={24} />
                      </button>
                    ))}
                  </div>
                )}
              </div>
              
              <div className="form-group">
                <label htmlFor="company-name">
                  <div className="label-logo-container">
                    <CurrentLogo size={14} />
                  </div>
                  {companyName || 'Nome da Empresa'}
                </label>
                <input
                  id="company-name"
                  type="text"
                  value={companyName}
                  onChange={(e) => setCompanyName(e.target.value)}
                  placeholder="Informe sua empresa"
                  maxLength={50}
                />
              </div>
              
              <div className="form-group">
                <label htmlFor="phone">
                  <Phone size={18} className="icon-phone" />
                  <svg className="icon-whatsapp" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.631 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                  </svg>
                  Telefone / WhatsApp
                </label>
                <input
                  id="phone"
                  type="text"
                  value={phone}
                  onChange={handlePhoneChange}
                  placeholder="(00) 00000-0000"
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
                    <textarea
                      className="item-description"
                      placeholder="Descrição de serviços"
                      value={item.description}
                      onChange={(e) => updateItem(index, 'description', e.target.value)}
                      rows={item.description.length > 40 ? 2 : 1}
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

              <div className="form-group" style={{ marginTop: '1.5rem' }}>
                <label htmlFor="observations">Observações</label>
                <textarea
                  id="observations"
                  className="observations-input"
                  value={observations}
                  onChange={(e) => setObservations(e.target.value)}
                  placeholder="Ex: Condições de pagamento, prazos..."
                  rows={4}
                />
              </div>
            </div>
          </section>

          {/* PREVIEW */}
          <section className="preview-section">
            <div className="preview-container">
              <h2>Visualização</h2>
              
              <div className="quote-preview" ref={quotePreviewRef}>
                <div className="quote-header">
                  <div className="quote-logo-large">
                    <CurrentLogo size={60} />
                  </div>
                  <div className="quote-company-info">
                    <h3>{companyName || 'Nome da Empresa'}</h3>
                    {phone && (
                      <div className="quote-contact-row">
                        <Phone size={16} color="#2563eb" className="icon-phone" />
                        <svg className="icon-whatsapp" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#25d366">
                          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.659 1.432 5.631 1.433h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                        <span style={{ fontSize: '14px', fontWeight: '500' }}>{phone}</span>
                      </div>
                    )}
                    <p>Orçamento Nº {budgetNumber}</p>
                  </div>
                </div>

                <table className="quote-table">
                  <thead>
                    <tr>
                      <th>Descrição</th>
                      <th>Valor</th>
                    </tr>
                  </thead>
                  <tbody>
                    {items.map((item, index) => (
                      <tr key={index}>
                        <td className="desc-cell">{item.description || '---'}</td>
                        <td className="right bold">R$ {(parseFloat(item.value) || 0).toFixed(2).replace('.', ',')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>

                {status === 'demo' && (
                  <div className="demo-banner">DEMONSTRAÇÃO</div>
                )}

                {observations && (
                  <div className="quote-observations-preview">
                    <strong>Observações:</strong>
                    <p>{observations}</p>
                  </div>
                )}

                <div className="quote-total">
                  <span>VALOR TOTAL:</span>
                  <span className="total-value">R$ {total.toFixed(2).replace('.', ',')}</span>
                </div>

                <div className="quote-footer">
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
              <QRCodeCanvas value={token} size={128} level="H" includeMargin={true} />
            </div>
            
            <p className="payment-info">
              Escaneie o código QR com seu celular para realizar o pagamento
            </p>
            
            <p className="payment-token">
              Token: <strong>{token}</strong>
            </p>
            
            <div className="payment-status">
              {status === 'profissa' ? (
                <p style={{ color: 'var(--success)', fontWeight: 'bold' }}>
                  ✅ Pagamento Aprovado!
                </p>
              ) : (
                <p>Aguardando confirmação de pagamento...</p>
              )}
              {status !== 'profissa' && <div className="spinner"></div>}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default App
