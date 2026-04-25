/**
 * Profissapp - Hook de Autenticação
 * Arquivo: src/hooks/useAuth.js
 */

import { useState, useEffect } from 'react';
import api from '../services/api';

export const useAuth = () => {
  const [token, setToken] = useState(null);
  const [status, setStatus] = useState('demo');
  const [daysRemaining, setDaysRemaining] = useState(0);
  const [restorationsAvailable, setRestorationsAvailable] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  /**
   * Carregar token do localStorage
   */
  useEffect(() => {
    const storedToken = localStorage.getItem('profissapp_token');
    if (storedToken) {
      setToken(storedToken);
      validateToken();
    }
  }, []);

  /**
   * Gerar novo token
   */
  const generateToken = async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.generateToken();
      if (response.success) {
        setToken(response.data.token);
        setStatus('demo');
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
   * Validar token
   */
  const validateToken = async () => {
    try {
      const response = await api.validateToken();
      if (response.success) {
        setStatus(response.data.status);
        setDaysRemaining(response.data.days_remaining || 0);
        setRestorationsAvailable(response.data.restorations_available || 0);
        return response.data;
      }
    } catch (err) {
      console.error('Token validation error:', err);
    }
  };

  /**
   * Restaurar token em novo dispositivo
   */
  const restoreToken = async (deviceId, deviceName) => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.restoreToken(deviceId, deviceName);
      if (response.success) {
        setToken(response.data.token);
        setStatus(response.data.status);
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
   * Logout
   */
  const logout = async () => {
    setLoading(true);
    try {
      await api.deleteToken();
      setToken(null);
      setStatus('demo');
      setDaysRemaining(0);
      setRestorationsAvailable(0);
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      setLoading(false);
    }
  };

  return {
    token,
    status,
    daysRemaining,
    restorationsAvailable,
    loading,
    error,
    generateToken,
    validateToken,
    restoreToken,
    logout,
    isProfissa: status === 'profissa',
    isDemo: status === 'demo'
  };
};
