/**
 * Profissapp - Serviço de API
 * Arquivo: src/services/api.js
 */

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

class ApiService {
  /**
   * Fazer requisição genérica
   */
  async request(endpoint, options = {}) {
    const url = `${API_BASE_URL}${endpoint}`;
    const headers = {
      'Content-Type': 'application/json',
      ...options.headers
    };

    // Adicionar token se existir
    const token = localStorage.getItem('profissapp_token');
    if (token && !endpoint.includes('generate-token')) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(url, {
        ...options,
        headers
      });

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `Erro ${response.status}`);
      }

      return data;
    } catch (error) {
      console.error('API Error:', error);
      throw error;
    }
  }

  /**
   * Gerar novo token
   */
  async generateToken() {
    const response = await this.request('/generate-token', {
      method: 'POST'
    });

    if (response.success && response.data?.token) {
      localStorage.setItem('profissapp_token', response.data.token);
      localStorage.setItem('profissapp_status', response.data.status || 'demo');
      localStorage.setItem('profissapp_created_at', new Date().toISOString());
    }

    return response;
  }

  /**
   * Validar token
   */
  async validateToken() {
    const token = localStorage.getItem('profissapp_token');
    
    if (!token) {
      return {
        success: false,
        message: 'Token não encontrado'
      };
    }

    const response = await this.request('/validate-token', {
      method: 'POST'
    });

    if (response.success) {
      localStorage.setItem('profissapp_status', response.data?.status || 'demo');
      localStorage.setItem('profissapp_days_remaining', response.data?.days_remaining || 0);
      localStorage.setItem('profissapp_restorations_available', response.data?.restorations_available || 0);
    }

    return response;
  }

  /**
   * Restaurar token
   */
  async restoreToken(deviceIdentifier, deviceName = 'Unknown Device') {
    const response = await this.request('/restore-token', {
      method: 'POST',
      body: JSON.stringify({
        device_identifier: deviceIdentifier,
        device_name: deviceName
      })
    });

    if (response.success && response.data?.token) {
      localStorage.setItem('profissapp_token', response.data.token);
      localStorage.setItem('profissapp_status', response.data.status || 'demo');
    }

    return response;
  }

  /**
   * Deletar token
   */
  async deleteToken() {
    const response = await this.request('/delete-token', {
      method: 'POST'
    });

    if (response.success) {
      localStorage.removeItem('profissapp_token');
      localStorage.removeItem('profissapp_status');
      localStorage.removeItem('profissapp_days_remaining');
      localStorage.removeItem('profissapp_restorations_available');
    }

    return response;
  }

  /**
   * Criar pagamento
   */
  async createPayment() {
    const response = await this.request('/create-payment', {
      method: 'POST'
    });

    if (response.success) {
      localStorage.setItem('profissapp_last_payment', response.data?.payment_id);
      localStorage.setItem('profissapp_payment_created_at', new Date().toISOString());
    }

    return response;
  }

  /**
   * Obter status do pagamento
   */
  async getPaymentStatus(paymentId) {
    return this.request(`/payment/${paymentId}`, {
      method: 'GET'
    });
  }

  /**
   * Listar pagamentos
   */
  async listPayments() {
    return this.request('/payments', {
      method: 'GET'
    });
  }

  /**
   * Confirmar pagamento
   */
  async confirmPayment(paymentId, mercadoPagoId) {
    const response = await this.request('/confirm-payment', {
      method: 'POST',
      body: JSON.stringify({
        payment_id: paymentId,
        mercado_pago_id: mercadoPagoId
      })
    });

    if (response.success) {
      localStorage.setItem('profissapp_status', 'profissa');
      localStorage.setItem('profissapp_payment_confirmed_at', new Date().toISOString());
    }

    return response;
  }

  /**
   * Reembolsar pagamento
   */
  async refundPayment(paymentId, amount = null) {
    return this.request('/refund-payment', {
      method: 'POST',
      body: JSON.stringify({
        payment_id: paymentId,
        amount: amount
      })
    });
  }

  /**
   * Obter estatísticas (admin)
   */
  async getStatistics() {
    return this.request('/statistics', {
      method: 'GET'
    });
  }

  /**
   * Health check
   */
  async healthCheck() {
    try {
      const response = await this.request('/health', {
        method: 'GET'
      });
      return response.success || false;
    } catch {
      return false;
    }
  }

  /**
   * Obter token armazenado localmente
   */
  getToken() {
    return localStorage.getItem('profissapp_token');
  }

  /**
   * Obter status do usuário
   */
  getStatus() {
    return localStorage.getItem('profissapp_status') || 'demo';
  }

  /**
   * Limpar dados locais
   */
  clearLocalStorage() {
    const keys = Object.keys(localStorage).filter(key => key.startsWith('profissapp_'));
    keys.forEach(key => localStorage.removeItem(key));
  }
}

export default new ApiService();
