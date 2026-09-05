import { Component, OnInit, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '../core/auth/auth.service';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  template: `
    <div class="shell">
      <aside class="sidebar">
        <div class="brand">
          <span class="brand-mark">AF</span>
          <span class="brand-name">AfriFlow AI</span>
        </div>

        <nav class="nav">
          <a routerLink="/dashboard" routerLinkActive="active">Tableau de bord</a>
          <a routerLink="/sales" routerLinkActive="active">Ventes</a>
          <a routerLink="/customers" routerLinkActive="active">Clients</a>
          <a routerLink="/products" routerLinkActive="active">Produits</a>
          <a routerLink="/expenses" routerLinkActive="active">Dépenses</a>
        </nav>
      </aside>

      <div class="main">
        <header class="topbar">
          <div class="company">{{ auth.currentUser()?.company?.name }}</div>
          <div class="user">
            <span>{{ auth.currentUser()?.fullName }}</span>
            <button class="btn btn-secondary" type="button" (click)="auth.logout()">
              Déconnexion
            </button>
          </div>
        </header>

        <main class="content">
          <router-outlet />
        </main>
      </div>
    </div>
  `,
  styles: [
    `
      .shell {
        display: flex;
        min-height: 100vh;
      }

      .sidebar {
        width: 232px;
        flex-shrink: 0;
        background: var(--color-primary-dark);
        color: white;
        display: flex;
        flex-direction: column;
        padding: 20px 0;
      }

      .brand {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0 20px 24px;
      }

      .brand-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--color-accent);
        color: #3a2600;
        font-weight: 700;
        font-size: 13px;
      }

      .brand-name {
        font-weight: 700;
        font-size: 16px;
      }

      .nav {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 0 12px;
      }

      .nav a {
        color: rgba(255, 255, 255, 0.78);
        padding: 10px 12px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
      }

      .nav a:hover {
        background: rgba(255, 255, 255, 0.08);
        color: white;
      }

      .nav a.active {
        background: rgba(255, 255, 255, 0.14);
        color: white;
      }

      .main {
        flex: 1;
        display: flex;
        flex-direction: column;
        min-width: 0;
      }

      .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 14px 28px;
        background: var(--color-surface);
        border-bottom: 1px solid var(--color-border);
      }

      .company {
        font-weight: 600;
      }

      .user {
        display: flex;
        align-items: center;
        gap: 12px;
        color: var(--color-text-muted);
        font-size: 13.5px;
      }

      .content {
        flex: 1;
        padding: 28px;
      }
    `,
  ],
})
export class ShellComponent implements OnInit {
  protected readonly auth = inject(AuthService);

  ngOnInit(): void {
    if (!this.auth.currentUser()) {
      this.auth.fetchCurrentUser().subscribe();
    }
  }
}
