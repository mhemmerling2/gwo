# GWO Apps Recruitment Task

Prosta aplikacja do zapisywania uczniów na wykłady.

Projekt jest napisany w PHP 8.5, Symfony 8 i MongoDB.

## Uruchomienie

Najpierw zainstaluj zależności:

```bash
make bootstrap
```

Potem uruchom aplikację:

```bash
make up
```

Na końcu utwórz indeksy w MongoDB:

```bash
make db-indexes
```

## Demo użytkownicy

Do ręcznego sprawdzenia API można utworzyć przykładowych użytkowników:

```bash
make demo-users
```

Ta komenda wypisze dane wykładowcy i ucznia razem z ich kluczami API.

## Dokumentacja API

Swagger UI:
`http://localhost:10990/docs`

OpenAPI:
`http://localhost:10990/openapi.yaml`

## Ręczne sprawdzenie

### Utworzenie wykładu

```bash
curl -i -X POST http://localhost:10990/lectures \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <LECTURER_API_KEY>' \
  -d '{
    "name": "Distributed Systems 101",
    "studentLimit": 25,
    "startDate": "2026-06-01T10:00:00+02:00",
    "endDate": "2026-06-01T12:00:00+02:00"
  }'
```

### Zapis ucznia na wykład

```bash
curl -i -X POST http://localhost:10990/lectures/<LECTURE_ID>/enrollments \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <STUDENT_API_KEY>'
```

### Przetworzenie kolejki

Zapisy na wykłady są obsługiwane asynchronicznie, więc trzeba uruchomić workera:

```bash
make consume-enrollments
```

### Lista wykładów ucznia

```bash
curl -i http://localhost:10990/students/me/lectures \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <STUDENT_API_KEY>'
```

### Usunięcie ucznia z wykładu

```bash
curl -i -X DELETE http://localhost:10990/lectures/<LECTURE_ID>/enrollments/<STUDENT_ID> \
  -H 'Accept: application/json' \
  -H 'X-Api-Key: <LECTURER_API_KEY>'
```

## Testy

Testy:

```bash
make tests
```

Coverage:

```bash
make coverage
```

PHPStan:

```bash
make phpstan
```

Pełna weryfikacja:

```bash
make qa
```

## Quality Gate

Repo ma workflow CI uruchamiany na `push` i `pull_request`:
- buduje środowisko Dockera,
- uruchamia pełny gate `make qa` (testy + coverage threshold + PHPStan),
- zapisuje artifact z raportem coverage (`clover.xml`).

Dzięki temu wynik lokalny i wynik w review są spójne.

## Uwagi

Wszystkie chronione endpointy korzystają z nagłówka `X-Api-Key`.

Projekt robiłem na Macu z Apple Silicon, ale użyte obrazy Dockera są wieloarchitekturowe, więc powinno działać też na x86.
