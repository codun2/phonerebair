export function useSuperAdmin() {
  const { api } = useApi()

  const stats = () => api('/super/stats')
  const featureCatalog = () => api('/super/feature-catalog')

  const shops = (params: Record<string, string> = {}) => {
    const q = new URLSearchParams(params).toString()
    return api('/super/shops' + (q ? `?${q}` : ''))
  }
  const showShop = (id: number) => api(`/super/shops/${id}`)
  const createShop = (body: any) => api('/super/shops', { method: 'POST', body })
  const updateShop = (id: number, body: any) => api(`/super/shops/${id}`, { method: 'PUT', body })
  const approveShop = (id: number) => api(`/super/shops/${id}/approve`, { method: 'POST' })
  const rejectShop = (id: number) => api(`/super/shops/${id}/reject`, { method: 'POST' })
  const renewShop = (id: number, body: any = {}) => api(`/super/shops/${id}/renew`, { method: 'POST', body })
  const deleteShop = (id: number) => api(`/super/shops/${id}`, { method: 'DELETE' })

  const plans = () => api('/super/plans')
  const createPlan = (body: any) => api('/super/plans', { method: 'POST', body })
  const updatePlan = (id: number, body: any) => api(`/super/plans/${id}`, { method: 'PUT', body })
  const deletePlan = (id: number) => api(`/super/plans/${id}`, { method: 'DELETE' })

  return {
    stats, featureCatalog, shops, showShop, createShop, updateShop, approveShop, rejectShop, renewShop, deleteShop,
    plans, createPlan, updatePlan, deletePlan,
  }
}
