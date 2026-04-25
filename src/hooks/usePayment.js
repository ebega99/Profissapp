/**
 * Profissapp - Hook de Pagamentos
 * Arquivo: src/hooks/usePayment.js
 */

import { useState } from 'react';
import api from '../services/api';

export const usePayment = () => {
  const [paymentId, setPaymentId] = useState(null);
  const [qrCode, setQrCode] = useState(null);
  const [copyPaste, setCopyPaste] = useState(null);
  const [paymentStatus, setPaymentStatus] = useState('pending');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  /**
   * Criar pagamento
   */
  const createPayment = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.createPayment();
      if (response.success) {
        const payment = response.data;
        setPaymentId(payment.payment_id);
        setQrCode(payment.qr_code);
        setCopyPaste(payment.copy_paste);
        setPaymentStatus('pending');
        return payment;
      } else {
        throw new Error(response.message);
      }
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  /**
   * Verificar status do pagamento
   */
  const checkPaymentStatus = async (payId = paymentId) => {
    if (!payId) {
      throw new Error('Payment ID não fornecido');
    }

    try {
      const response = await api.getPaymentStatus(payId);
      if (response.success) {
        setPaymentStatus(response.data.status);
        return response.data;
      }
    } catch (err) {
      console.error('Payment status check error:', err);
      throw err;
    }
  };

  /**
   * Monitorar pagamento (polling)
   */
  const monitorPayment = async (payId = paymentId, interval = 3000, maxAttempts = 60) => {
    let attempts = 0;

    return new Promise((resolve, reject) => {
      const timer = setInterval(async () => {
        attempts++;

        try {
          const status = await checkPaymentStatus(payId);

          if (status.status === 'approved') {
            clearInterval(timer);
            resolve(status);
          } else if (status.status === 'failed' || status.status === 'cancelled') {
            clearInterval(timer);
            reject(new Error(`Payment ${status.status}`));
          } else if (attempts >= maxAttempts) {
            clearInterval(timer);
            reject(new Error('Payment verification timeout'));
          }
        } catch (err) {
          if (attempts >= maxAttempts) {
            clearInterval(timer);
            reject(err);
          }
        }
      }, interval);
    });
  };

  /**
   * Confirmar pagamento
   */
  const confirmPayment = async (payId = paymentId, mpId) => {
    if (!payId || !mpId) {
      throw new Error('Payment ID ou Mercado Pago ID não fornecido');
    }

    setLoading(true);
    setError(null);
    try {
      const response = await api.confirmPayment(payId, mpId);
      if (response.success) {
        setPaymentStatus('approved');
        return response.data;
      } else {
        throw new Error(response.message);
      }
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  /**
   * Reembolsar pagamento
   */
  const refundPayment = async (payId = paymentId, amount = null) => {
    if (!payId) {
      throw new Error('Payment ID não fornecido');
    }

    setLoading(true);
    setError(null);
    try {
      const response = await api.refundPayment(payId, amount);
      if (response.success) {
        setPaymentStatus('refunded');
        return response.data;
      } else {
        throw new Error(response.message);
      }
    } catch (err) {
      setError(err.message);
      throw err;
    } finally {
      setLoading(false);
    }
  };

  /**
   * Copiar código PIX para clipboard
   */
  const copyPixCode = async () => {
    if (!copyPaste) {
      throw new Error('Código PIX não disponível');
    }

    try {
      await navigator.clipboard.writeText(copyPaste);
      return true;
    } catch (err) {
      console.error('Copy error:', err);
      throw err;
    }
  };

  /**
   * Resetar estado
   */
  const reset = () => {
    setPaymentId(null);
    setQrCode(null);
    setCopyPaste(null);
    setPaymentStatus('pending');
    setError(null);
  };

  return {
    paymentId,
    qrCode,
    copyPaste,
    paymentStatus,
    loading,
    error,
    createPayment,
    checkPaymentStatus,
    monitorPayment,
    confirmPayment,
    refundPayment,
    copyPixCode,
    reset,
    isApproved: paymentStatus === 'approved',
    isPending: paymentStatus === 'pending',
    isFailed: paymentStatus === 'failed'
  };
};
