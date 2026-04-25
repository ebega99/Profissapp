/**
 * Profissapp - Exemplo: Componente de Pagamento
 * Arquivo: src/components/PaymentModal.example.jsx
 */

import React, { useState, useEffect } from 'react';
import { QRCodeSVG } from 'qrcode.react';
import { Copy, CheckCircle, AlertCircle, Loader } from 'lucide-react';
import { useAuth } from '../hooks/useAuth';
import { usePayment } from '../hooks/usePayment';
import '../styles/PaymentModal.css';

export const PaymentModal = ({ isOpen, onClose }) => {
  const { token, isProfissa } = useAuth();
  const {
    paymentId,
    qrCode,
    copyPaste,
    paymentStatus,
    loading,
    error,
    createPayment,
    monitorPayment,
    copyPixCode,
    reset
  } = usePayment();

  const [copied, setCopied] = useState(false);
  const [isMonitoring, setIsMonitoring] = useState(false);

  // Iniciar monitoramento após criar pagamento
  useEffect(() => {
    if (paymentId && paymentStatus === 'pending' && !isMonitoring) {
      setIsMonitoring(true);
      monitorPayment()
        .then(() => {
          // Pagamento aprovado!
          setTimeout(() => {
            onClose();
          }, 2000);
        })
        .catch((err) => {
          console.error('Payment monitoring error:', err);
          setIsMonitoring(false);
        });
    }
  }, [paymentId, paymentStatus]);

  const handleCreatePayment = async () => {
    try {
      await createPayment();
    } catch (err) {
      console.error('Payment creation error:', err);
    }
  };

  const handleCopyPix = async () => {
    try {
      await copyPixCode();
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.error('Copy error:', err);
    }
  };

  const handleClose = () => {
    reset();
    onClose();
  };

  if (!isOpen) return null;

  // Se usuário já é Profissa
  if (isProfissa) {
    return (
      <div className="payment-modal-overlay" onClick={handleClose}>
        <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
          <button className="modal-close" onClick={handleClose}>×</button>
          <div className="payment-success">
            <CheckCircle size={64} className="icon-success" />
            <h2>Você já é Profissa!</h2>
            <p>Sua assinatura está ativa e válida.</p>
            <button className="btn-primary" onClick={handleClose}>Fechar</button>
          </div>
        </div>
      </div>
    );
  }

  // Se não tem token
  if (!token) {
    return (
      <div className="payment-modal-overlay" onClick={handleClose}>
        <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
          <button className="modal-close" onClick={handleClose}>×</button>
          <div className="payment-error">
            <AlertCircle size={64} className="icon-error" />
            <h2>Token não encontrado</h2>
            <p>Por favor, gere um novo token antes de fazer o pagamento.</p>
            <button className="btn-primary" onClick={handleClose}>Fechar</button>
          </div>
        </div>
      </div>
    );
  }

  // Se houve erro
  if (error && !paymentId) {
    return (
      <div className="payment-modal-overlay" onClick={handleClose}>
        <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
          <button className="modal-close" onClick={handleClose}>×</button>
          <div className="payment-error">
            <AlertCircle size={64} className="icon-error" />
            <h2>Erro ao criar pagamento</h2>
            <p>{error}</p>
            <button className="btn-primary" onClick={() => {
              reset();
              handleCreatePayment();
            }}>
              Tentar Novamente
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Se pagamento foi aprovado
  if (paymentStatus === 'approved') {
    return (
      <div className="payment-modal-overlay" onClick={handleClose}>
        <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
          <button className="modal-close" onClick={handleClose}>×</button>
          <div className="payment-success">
            <CheckCircle size={64} className="icon-success" />
            <h2>Pagamento Aprovado! ✅</h2>
            <p>Bem-vindo ao Profissapp Premium!</p>
            <p className="payment-details">
              Seu acesso foi ativado e é válido por 30 dias.
            </p>
            <button className="btn-primary" onClick={handleClose}>Fechar</button>
          </div>
        </div>
      </div>
    );
  }

  // Se não há pagamento criado
  if (!paymentId) {
    return (
      <div className="payment-modal-overlay" onClick={handleClose}>
        <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
          <button className="modal-close" onClick={handleClose}>×</button>
          <div className="payment-create">
            <h2>Ativar Profissapp Premium</h2>
            <p className="price">R$ 5,00 por 30 dias</p>
            
            <div className="benefits">
              <h3>Benefícios:</h3>
              <ul>
                <li>✅ Acesso ilimitado ao gerador de orçamentos</li>
                <li>✅ 3 restaurações de token por mês</li>
                <li>✅ Exportação em alta qualidade</li>
                <li>✅ Suporte prioritário</li>
              </ul>
            </div>

            <button
              className="btn-primary btn-large"
              onClick={handleCreatePayment}
              disabled={loading}
            >
              {loading ? (
                <>
                  <Loader size={20} className="spinner" />
                  Criando pagamento...
                </>
              ) : (
                'Pagar com PIX'
              )}
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Exibir QR Code e aguardar pagamento
  return (
    <div className="payment-modal-overlay" onClick={handleClose}>
      <div className="payment-modal-content" onClick={(e) => e.stopPropagation()}>
        <button className="modal-close" onClick={handleClose}>×</button>
        
        <div className="payment-waiting">
          <h2>Escaneie o QR Code com seu banco</h2>
          
          <div className="qr-container">
            {qrCode ? (
              <QRCodeSVG
                value={qrCode}
                size={250}
                level="H"
                includeMargin={true}
              />
            ) : (
              <div className="qr-placeholder">
                <Loader size={40} className="spinner" />
              </div>
            )}
          </div>

          <div className="pix-code-section">
            <p className="label">Ou copie o código PIX:</p>
            <div className="pix-code-container">
              <code className="pix-code">{copyPaste?.substring(0, 50)}...</code>
              <button
                className="btn-copy"
                onClick={handleCopyPix}
                title="Copiar código PIX"
              >
                <Copy size={20} />
              </button>
            </div>
            {copied && <p className="copy-feedback">✓ Copiado!</p>}
          </div>

          <div className="payment-info">
            <p><strong>Valor:</strong> R$ 5,00</p>
            <p><strong>Período:</strong> 30 dias</p>
            <p className="info-note">
              {isMonitoring ? (
                <>
                  <Loader size={16} className="spinner" /> Aguardando confirmação...
                </>
              ) : (
                'Confirme o pagamento no seu banco'
              )}
            </p>
          </div>

          <button
            className="btn-secondary"
            onClick={handleClose}
            disabled={isMonitoring}
          >
            Cancelar
          </button>
        </div>
      </div>
    </div>
  );
};

export default PaymentModal;
