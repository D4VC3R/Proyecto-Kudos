export const AsyncSection = ({
  isLoading,
  isError,
  error,
  isEmpty,
  LoadingComponent,
  ErrorComponent,
  EmptyComponent,
  children,
}) => {
  if (isLoading && LoadingComponent) {
    return <LoadingComponent />;
  }

  if (isError && ErrorComponent) {
    return <ErrorComponent error={error} />;
  }
  if (isEmpty && EmptyComponent) {
    return <EmptyComponent />;
  }

  if (!isLoading && !isError && !isEmpty) {
    return <>{children}</>;
  }

  return null;
};

