export function useConfirm() {
  const state = useState<any>('sr_confirm', () => ({ open: false, title: '', text: '', resolve: null }))

  function confirmDlg(title: string, text = ''): Promise<boolean> {
    return new Promise((resolve) => {
      state.value = { open: true, title, text, resolve }
    })
  }

  function answer(v: boolean) {
    state.value.resolve?.(v)
    state.value = { ...state.value, open: false, resolve: null }
  }

  return { state, confirmDlg, answer }
}
