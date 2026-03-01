export function cn(...classes: any[]) {
  return classes.filter(Boolean).join(" ");
}

export function toUrl(route: any) {
  if (!route) return '#';

  // If it's already a string URL
  if (typeof route === 'string') return route;

  // If it's a route object (from Ziggy / Wayfinder)
  if (route.url) return route.url;

  return '#';
}

export function urlIsActive(route: any) {
  if (!route) return false;

  const current = window.location.pathname;
  const target = toUrl(route);

  return current === target;
}
