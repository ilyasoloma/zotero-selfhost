function randomType() {
  return '@@is-valid-redux-reducer/' + Math.random().toString(36).substring(7).split('').join('\\')
}

export default function isValidReduxReducer(reducer, throwError = false) {
  if (typeof reducer !== 'function') {
    if (throwError) {
      throw new Error('Reducer must be a function.')
    } else {
      return false
    }
  }

  const initialState = reducer(undefined, {type: randomType()})
  if (typeof initialState === 'undefined') {
    if (throwError) {
      throw new Error('Reducer must return the initial state if the state is undefined.')
    } else {
      return false
    }
  }

  return true
}
