import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/auth/auth.service';

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  template: `
    <div class="auth-page">
      <form class="card auth-card" [formGroup]="form" (ngSubmit)="submit()">
        <div class="auth-brand">
          <span class="brand-mark">AF</span>
          <span>AfriFlow AI</span>
        </div>
        <h1>Créer votre compte</h1>
        <p class="subtitle">Démarrez la gestion de votre entreprise en quelques secondes.</p>

        @if (error()) {
          <div class="alert-error">{{ error() }}</div>
        }

        <div class="form-field">
          <label for="companyName">Nom de l'entreprise</label>
          <input id="companyName" type="text" formControlName="companyName" />
        </div>

        <div class="form-field">
          <label for="fullName">Votre nom complet</label>
          <input id="fullName" type="text" formControlName="fullName" />
        </div>

        <div class="form-field">
          <label for="email">Email</label>
          <input id="email" type="email" formControlName="email" autocomplete="email" />
        </div>

        <div class="form-field">
          <label for="password">Mot de passe</label>
          <input
            id="password"
            type="password"
            formControlName="password"
            autocomplete="new-password"
          />
          <span class="field-hint">8 caractères minimum.</span>
        </div>

        <button class="btn btn-primary auth-submit" type="submit" [disabled]="form.invalid || loading()">
          {{ loading() ? 'Création...' : 'Créer mon compte' }}
        </button>

        <p class="switch-link">
          Déjà un compte ? <a routerLink="/login">Se connecter</a>
        </p>
      </form>
    </div>
  `,
  styles: [
    `
      .auth-page {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--color-bg);
        padding: 24px;
      }

      .auth-card {
        width: 100%;
        max-width: 400px;
      }

      .auth-brand {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 700;
        margin-bottom: 20px;
      }

      .brand-mark {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 7px;
        background: var(--color-accent);
        color: #3a2600;
        font-size: 12px;
      }

      .subtitle {
        color: var(--color-text-muted);
        margin: 0 0 20px;
        font-size: 13.5px;
      }

      .field-hint {
        color: var(--color-text-muted);
        font-size: 12px;
      }

      .auth-submit {
        width: 100%;
        justify-content: center;
        margin-top: 4px;
      }

      .switch-link {
        text-align: center;
        margin: 18px 0 0;
        font-size: 13.5px;
        color: var(--color-text-muted);
      }
    `,
  ],
})
export class RegisterComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly loading = signal(false);
  protected readonly error = signal<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    companyName: ['', Validators.required],
    fullName: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
  });

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    const { email, password } = this.form.getRawValue();

    this.auth.register(this.form.getRawValue()).subscribe({
      next: () => {
        this.auth.login({ email, password }).subscribe({
          next: () => {
            this.loading.set(false);
            this.router.navigateByUrl('/dashboard');
          },
          error: () => {
            this.loading.set(false);
            this.router.navigateByUrl('/login');
          },
        });
      },
      error: (err) => {
        this.loading.set(false);
        this.error.set(
          err?.status === 409
            ? 'Un compte existe déjà avec cet email.'
            : 'Une erreur est survenue, veuillez réessayer.',
        );
      },
    });
  }
}
